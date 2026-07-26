<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Get reviews for a trip
     */
    public function index(Request $request)
    {
        $tripId = $request->query('trip_id');
        $query = Review::with('user');

        if ($tripId) {
            $query->where('trip_id', $tripId);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Create a new review
     */
    public function store(Request $request)
    {
        $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'place_name' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string',
        ]);

        $review = Review::create([
            'user_id' => Auth::id(),
            'trip_id' => $request->trip_id,
            'place_name' => $request->place_name,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
        ]);

        return response()->json($review, 201);
    }

    /**
     * Delete a review
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        // Check if user owns the review
        if ($review->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted']);
    }
}
