<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\Trip;
use App\Services\WeatherService;
use App\Services\PlaceSearchService;
use App\Services\RouteService;
use Illuminate\Http\Request;

class ItineraryController extends Controller
{
    protected $weatherService;
    protected $placeSearchService;
    protected $routeService;

    public function __construct(
        WeatherService $weatherService,
        PlaceSearchService $placeSearchService,
        RouteService $routeService
    ) {
        $this->weatherService = $weatherService;
        $this->placeSearchService = $placeSearchService;
        $this->routeService = $routeService;
    }

    // Add place to itinerary
    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'place_id' => 'required',
            'place_name' => 'required',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'day_number' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
            'time' => 'nullable|date_format:H:i',
            'duration_minutes' => 'nullable|integer',
        ]);

        $trip = Trip::findOrFail($request->trip_id);
        
        // Authorization check (ensure user owns trip)
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get weather (Handle failures gracefully)
        $weather = [];
        try {
            $weather = $this->weatherService->getWeather($request->lat, $request->lng);
        } catch (\Exception $e) {
            // Ignore weather errors, proceed with itinerary creation
            \Illuminate\Support\Facades\Log::warning('Weather service failed: ' . $e->getMessage());
        }

        // Find gas stations (Handle failures gracefully)
        $gasStations = [];
        try {
            $gasStations = $this->placeSearchService->findGasStations($request->lat, $request->lng);
        } catch (\Exception $e) {
            // Ignore search errors
        }

        $itinerary = Itinerary::create([
            'trip_id' => $request->trip_id,
            'user_id' => $request->user()->id,
            'place_id' => $request->place_id,
            'place_name' => $request->place_name,
            'place_address' => $request->place_address ?? null,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'day_number' => $request->day_number,
            'order' => $request->order,
            'time' => $request->time,
            'duration_minutes' => $request->duration_minutes ?? 120,
            'weather_summary' => $weather['summary'] ?? null,
            'weather_icon' => $weather['icon'] ?? null,
            // FIX: Ensure this is saved as JSON string if not cast in model
            'nearby_gas_stations' => json_encode($gasStations), 
        ]);

        return response()->json($itinerary, 201);
    }

    // Update itinerary item
    public function update(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
        
        // 1. Manually check ownership if Policy is missing/failing
        if ($request->user()->id !== $itinerary->trip->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 2. Validate Input
        $request->validate([
            'time' => 'nullable|date_format:H:i',
            'duration_minutes' => 'nullable|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        // 3. Filter Input
        // Only allow updating these specific fields to avoid overwriting computed data
        // like lat/lng/gas stations accidentally.
        $data = $request->only([
            'time', 
            'duration_minutes', 
            'notes', 
            'day_number', 
            'order'
        ]);

        // 4. Update
        $itinerary->update($data);

        return response()->json($itinerary);
    }

    // Delete itinerary item
    public function destroy($id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $this->authorize('delete', $itinerary->trip);
        $itinerary->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Get itineraries for a trip
    public function byTrip($tripId)
    {
        $itineraries = Itinerary::where('trip_id', $tripId)
            ->orderBy('day_number')
            ->orderBy('order')
            ->get();
        return response()->json($itineraries);
    }

    // Calculate route for all itinerary items
    public function calculateRoute($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        $itineraries = $trip->itineraries()->orderBy('day_number')->orderBy('order')->get();

        if ($itineraries->count() < 2) {
            return response()->json(['error' => 'Need at least 2 locations'], 400);
        }

        // Extract waypoints
        $waypoints = $itineraries->map(fn($i) => [$i->lat, $i->lng])->toArray();

        // Get route alternatives
        $routeService = new RouteService();
        $alternatives = $routeService->getRouteAlternatives($waypoints);

        if (empty($alternatives)) {
            return response()->json(['error' => 'Could not calculate route'], 400);
        }

        // Update trip with primary route
        $primaryRoute = reset($alternatives);
        $trip->update([
            'route_data' => json_encode($primaryRoute['geometry'] ?? null)
        ]);

        // Update each itinerary with distance and time from previous
        $legs = $primaryRoute['legs'] ?? [];
        foreach ($legs as $index => $leg) {
            if ($index + 1 < $itineraries->count()) {
                $itineraries[$index + 1]->update([
                    'distance_from_previous' => $leg['distance'] / 1000, // Convert to km
                    'drive_time_from_previous' => intval($leg['duration'] / 60), // Convert to minutes
                ]);
            }
        }

        return response()->json([
            'trip' => $trip,
            'itineraries' => $itineraries,
            'alternatives' => $alternatives
        ]);
    }

    // Get route details (speed limits, turns, etc)
    public function routeDetails($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        $itineraries = $trip->itineraries()->orderBy('day_number')->orderBy('order')->get();

        if ($itineraries->count() < 2) {
            return response()->json(['error' => 'Need at least 2 locations'], 400);
        }

        $waypoints = $itineraries->map(fn($i) => [$i->lat, $i->lng])->toArray();

        $routeService = new RouteService();
        
        try {
            $route = $routeService->calculateRoute($waypoints, $trip->transit_type ?? 'car');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Route Calculation Failed: " . $e->getMessage());
            return response()->json(['error' => 'Route service unavailable'], 500);
        }

        // Validate response structure
        if (!$route || !isset($route['routes'][0])) {
            return response()->json(['error' => 'Could not calculate route'], 400);
        }

        $speedInfo = $routeService->getSpeedLimits($route['routes'][0]);

        return response()->json([
            'route' => $route['routes'][0],
            'speed_limits' => $speedInfo,
            'total_distance' => ($route['routes'][0]['distance'] ?? 0) / 1000,
            'total_duration' => ($route['routes'][0]['duration'] ?? 0) / 60,
        ]);
    }

    // Search places for adding to itinerary
    public function searchPlaces(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        // FIX: Ensure this calls the method we renamed in Step 2 of the previous turn
        $places = $this->placeSearchService->searchPlaces(
            $request->query('query'), // Use 'query' method to get GET param safely
            $request->lat,
            $request->lng,
            $request->radius ?? 10000
        );

        return response()->json($places);
    }

    // Get suggestions between two waypoints
    public function suggestPlaces(Request $request)
    {
        $request->validate([
            'from_lat' => 'required|numeric',
            'from_lng' => 'required|numeric',
            'to_lat' => 'required|numeric',
            'to_lng' => 'required|numeric',
        ]);

        // Calculate midpoint
        $midLat = ($request->from_lat + $request->to_lat) / 2;
        $midLng = ($request->from_lng + $request->to_lng) / 2;

        // Suggest restaurants, hotels, gas stations
        $suggestions = $this->placeSearchService->searchNearby(
            $midLat,
            $midLng,
            ['restaurant', 'hotel', 'cafe', 'fuel']
        );

        return response()->json($suggestions);
    }
}
