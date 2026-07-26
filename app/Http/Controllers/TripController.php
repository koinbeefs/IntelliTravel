<?php declare(strict_types=1);

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
        // Get trips owned by user
        $ownedTrips = $request->user()->trips()
            ->with(['users', 'itineraries', 'owner'])
            ->withCount('itineraries')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get shared trips where user is an accepted collaborator via pivot table
        $sharedTrips = Trip::whereHas('users', function ($query) use ($request) {
            $query->where('trip_user.user_id', $request->user()->id)
                  ->where('trip_user.status', 'accepted');
        })
        ->with(['users', 'itineraries', 'owner'])
        ->withCount('itineraries')
        ->orderBy('created_at', 'desc')
        ->get();

        // Get pending trip invitations from notifications
        $pendingNotifications = \App\Models\Notification::where('user_id', $request->user()->id)
            ->where('type', 'trip_invite')
            ->where('read', false)
            ->get();

        $pendingTripIds = $pendingNotifications->pluck('data.trip_id')->filter();
        $pendingTrips = Trip::whereIn('id', $pendingTripIds)
            ->with(['users', 'itineraries', 'owner'])
            ->withCount('itineraries')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trip) {
                $trip->invitation_status = 'pending';
                return $trip;
            });

        // Merge and remove duplicates (in case user is both owner and collaborator)
        $allTrips = $ownedTrips->merge($sharedTrips)->merge($pendingTrips)->unique('id');

        return $allTrips->values();
    }

    // Get single trip with itinerary
    public function show(Request $request, $id)
    {
        $trip = Trip::with(['itineraries', 'users', 'owner'])->findOrFail($id);

        $isCollaborator = $trip->user_id === $request->user()->id || 
            $trip->users()->where('user_id', $request->user()->id)->where('status', 'accepted')->exists();

        if (!$isCollaborator) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($trip);
    }

    // Create new trip (manual or auto)
    public function store(Request $request)
    {
        // 1. Validate
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'trip_type' => 'required|in:manual,automatic',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'transit_type' => 'required|in:car,bus,train,plane,ferry,bike,walk',
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
            'is_active' => false,      // Default to planning
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
        
        $isCollaborator = $trip->user_id === $request->user()->id || 
            $trip->users()->where('user_id', $request->user()->id)->where('status', 'accepted')->exists();

        if (!$isCollaborator) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'destination' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'transit_type' => 'sometimes|required|in:car,bus,train,plane,ferry,bike,walk',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
        ]);

        $trip->update($validated);
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

    $startDate = \Illuminate\Support\Carbon::parse($trip->start_date);
    $endDate = \Illuminate\Support\Carbon::parse($trip->end_date);

    $days = $trip->start_date && $trip->end_date
        ? $startDate->diffInDays($endDate) + 1
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
