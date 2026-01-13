<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripInvitationController extends Controller
{
    // POST /trips/{id}/invite
    public function invite(Request $request, $tripId)
    {
        $request->validate(['email' => 'required|email']);
        
        $trip = Trip::findOrFail($tripId);

        // Only owner can invite
        if ($request->user()->id !== $trip->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $invitee = User::where('email', $request->email)->first();

        if (!$invitee) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if already member
        if ($trip->users()->where('user_id', $invitee->id)->exists()) {
            return response()->json(['message' => 'User is already a member'], 400);
        }

        // Attach user to trip (pivot table trip_user needs to exist)
        // If you don't have a trip_user table yet, we assume you have a 'users' relationship on Trip model
        // If not, use DB facade or create the migration: php artisan make:migration create_trip_user_table
        
        // Assuming Many-to-Many relationship defined in Trip model
        $trip->users()->attach($invitee->id, ['role' => 'editor']);

        return response()->json(['message' => 'Invitation sent!']);
    }

    // GET /trips/{id}/collaborators
    public function collaborators($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        // Include owner + invited users
        $users = $trip->users()->get()->merge([$trip->owner]); 
        
        return response()->json($users->unique('id'));
    }
}
