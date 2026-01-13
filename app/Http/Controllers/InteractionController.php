<?php

namespace App\Http\Controllers;

use App\Models\UserInteraction;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'place_id' => 'required',
            'place_name' => 'required',
            'category' => 'required'
        ]);

        UserInteraction::create([
            'user_id' => $request->user()->id,
            'place_id' => $request->place_id,
            'place_name' => $request->place_name,
            'category' => $request->category
        ]);

        return response()->json(['message' => 'Interaction recorded']);
    }
}
