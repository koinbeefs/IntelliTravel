<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Services\RecommendationEngine;
use Illuminate\Http\Request;
use Carbon\Carbon; // <--- IMPORTANT: Add this import

class TripController extends Controller
{
    protected $recommendationEngine;

    public function __construct(RecommendationEngine $recommendationEngine)
    {
        $this->recommendationEngine = $recommendationEngine;
    }

    // Get all user trips
    public function index(Request $request)
    {
        return $request->user()->trips()
            ->withCount('itineraries')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Get single trip with itinerary
    public function show($id)
    {
        $trip = Trip::with('itineraries')->findOrFail($id);
        return response()->json($trip);
    }

    // Create new trip (manual or auto)
    public function store(Request $request)
    {
        // 1. Validate
        $validated = $request->validate([
            'title' => 'required|string',
            'destination' => 'required|string',
            'trip_type' => 'required|in:manual,automatic',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'transit_type' => 'required|in:car,bike,walk,bus',
            'center_lat' => 'required|numeric',
            'center_lng' => 'required|numeric',
            'description' => 'nullable|string'
        ]);

        // 2. FIX: Parse dates using Carbon before calculating
        $start = Carbon::parse($validated['start_date']);
        $end = $validated['end_date'] ? Carbon::parse($validated['end_date']) : $start->copy();
        
        $days = $start->diffInDays($end) + 1;

        // 3. Create Trip
        $trip = Trip::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'destination' => $validated['destination'],
            'trip_type' => $validated['trip_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
            'transit_type' => $validated['transit_type'],
            'center_lat' => $validated['center_lat'],
            'center_lng' => $validated['center_lng'],
            'description' => $request->description ?? null,
            'is_active' => true,      // Default to active
            'is_published' => false   // Default to private
        ]);

        // 4. If automatic, generate itinerary
        if ($validated['trip_type'] === 'automatic') {
            try {
                $this->recommendationEngine->generateAutoItinerary(
                    $request->user()->id,
                    $validated['destination'],
                    $days,
                    $validated['center_lat'],
                    $validated['center_lng']
                );
            } catch (\Exception $e) {
                // If AI fails, don't crash the whole trip creation, just log it
                \Illuminate\Support\Facades\Log::error("Auto Itinerary Failed: " . $e->getMessage());
            }
        }

        return response()->json($trip, 201);
    }

    // Update trip
    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        // Ensure user owns the trip
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $trip->update($request->all());
        return response()->json($trip);
    }

    // Delete trip
    public function destroy(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $trip->delete();
        return response()->json(['message' => 'Trip deleted']);
    }

    // Mark trip as active
    public function activate(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Deactivate all user's trips
        Trip::where('user_id', $trip->user_id)->update(['is_active' => false]);
        
        // Activate this one
        $trip->update(['is_active' => true]);

        return response()->json($trip);
    }

    // Get recommendations
    public function getRecommendations(Trip $trip, RecommendationEngine $engine)
{
    $user = auth()->user();

    $days = $trip->start_date && $trip->end_date
        ? $trip->start_date->diffInDays($trip->end_date) + 1
        : 3;

    $items = $engine->generateAutoItinerary(
        $user->id,
        $trip->destination,
        $days,
        $trip->center_lat,
        $trip->center_lng
    );

    // Always respond with an array field
    return response()->json([
        'recommendations' => $items ?? [],
    ]);
}

}
