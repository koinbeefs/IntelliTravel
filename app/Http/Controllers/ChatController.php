<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\ChatMessage;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // GET /trips/{id}/messages
    public function index($tripId)
    {
        // Simple authorization: Must be part of trip
        $trip = Trip::findOrFail($tripId);
        // Add check if user is in trip->users()...

        return ChatMessage::where('trip_id', $tripId)
            ->with('user:id,username,profile_pic')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // POST /trips/{id}/messages
    public function store(Request $request, $tripId)
    {
        $request->validate(['content' => 'required|string']);

        $message = ChatMessage::create([
            'trip_id' => $tripId,
            'user_id' => $request->user()->id,
            'content' => $request->content
        ]);

        return response()->json($message->load('user'), 201);
    }
}
