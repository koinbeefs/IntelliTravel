<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use App\Services\RecommendationEngine;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    protected $recommendationEngine;

    public function __construct(RecommendationEngine $recommendationEngine)
    {
        $this->recommendationEngine = $recommendationEngine;
    }

    // Get user preferences
    public function show(Request $request)
    {
        $preferences = $request->user()->preferences ?? UserPreference::create([
            'user_id' => $request->user()->id,
        ]);

        return response()->json($preferences);
    }

    // Update preferences
    public function update(Request $request)
    {
        $preferences = $request->user()->preferences ?? UserPreference::create([
            'user_id' => $request->user()->id,
        ]);

        $preferences->update($request->all());

        return response()->json($preferences);
    }

    // Analyze trip history and auto-generate preferences
    public function analyzeHistory(Request $request)
    {
        $analyzed = $this->recommendationEngine->analyzeUserHistory($request->user()->id);

        if (!$analyzed) {
            return response()->json(['message' => 'No trip history to analyze'], 400);
        }

        $preferences = $request->user()->preferences ?? UserPreference::create([
            'user_id' => $request->user()->id,
        ]);

        $preferences->update($analyzed);

        return response()->json([
            'message' => 'Preferences updated based on history',
            'preferences' => $preferences
        ]);
    }
}
