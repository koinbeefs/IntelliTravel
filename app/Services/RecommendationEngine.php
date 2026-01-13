<?php

namespace App\Services;

use App\Models\TripVisit;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Log;

class RecommendationEngine
{
    /**
     * Analyze user's trip history and generate preferences
     */
    public function analyzeUserHistory($userId)
    {
        // Get user's past visits
        $visits = TripVisit::where('user_id', $userId)
            ->where('visited_at', '>=', now()->subMonths(12))
            ->get();

        if ($visits->isEmpty()) {
            return null; // No history, use defaults
        }

        // Count category visits
        $categoryCounts = $visits->groupBy('place_category')->map->count();
        $totalVisits = $visits->count();

        // Calculate preference scores (0-100)
        $preferences = [];
        $categories = [
            'restaurant', 'hotel', 'shopping', 'coffee',
            'attractions', 'nature', 'culture', 'adventure'
        ];

        foreach ($categories as $cat) {
            $count = $categoryCounts[$cat] ?? 0;
            $preferences["preference_$cat"] = intval(($count / $totalVisits) * 100);
        }

        // Calculate average duration at each place
        $avgDuration = intval($visits->avg('duration_minutes'));
        $preferences['avg_hours_per_stop'] = intval($avgDuration / 60);

        return $preferences;
    }

    /**
     * Generate automatic itinerary based on user preferences
     */
    public function generateAutoItinerary($userId, $destination, $days, $centerLat, $centerLng)
{
    $preferences = UserPreference::where('user_id', $userId)->first();
    
    // Fallback preferences if none stored
    if (!$preferences) {
        $preferences = (object)[
            'preference_culture'   => 50,
            'preference_coffee'    => 50,
            'preference_shopping'  => 50,
            'avg_hours_per_stop'   => 3,   // 3 hours per stop
        ];
    }

    // Avoid division by zero
    $avgHours = $preferences->avg_hours_per_stop ?: 3;
    $placesPerDay = max(1, intval(24 / $avgHours));

    $itineraryItems = [];

    for ($day = 1; $day <= $days; $day++) {
        $itemsThisDay = 0;

        if ($preferences->preference_culture > 40 && $itemsThisDay < $placesPerDay) {
            $itineraryItems[] = [
                'day'      => $day,
                'order'    => $itemsThisDay++,
                'time'     => '09:00',
                'type'     => 'attraction',
                'duration' => $avgHours * 60,
                'score'    => $preferences->preference_culture,
            ];
        }

        if ($preferences->preference_coffee > 40 && $itemsThisDay < $placesPerDay) {
            $itineraryItems[] = [
                'day'      => $day,
                'order'    => $itemsThisDay++,
                'time'     => '11:30',
                'type'     => 'coffee',
                'duration' => 45,
                'score'    => $preferences->preference_coffee,
            ];
        }

        if ($itemsThisDay < $placesPerDay) {
            $itineraryItems[] = [
                'day'      => $day,
                'order'    => $itemsThisDay++,
                'time'     => '12:30',
                'type'     => 'restaurant',
                'duration' => 90,
                'score'    => 100,
            ];
        }

        if ($preferences->preference_shopping > 40 && $itemsThisDay < $placesPerDay) {
            $itineraryItems[] = [
                'day'      => $day,
                'order'    => $itemsThisDay++,
                'time'     => '15:00',
                'type'     => 'shopping',
                'duration' => $avgHours * 60,
                'score'    => $preferences->preference_shopping,
            ];
        }

        if ($itemsThisDay < $placesPerDay) {
            $itineraryItems[] = [
                'day'      => $day,
                'order'    => $itemsThisDay++,
                'time'     => '18:30',
                'type'     => 'restaurant',
                'duration' => 120,
                'score'    => 90,
            ];
        }
    }

    return $itineraryItems;
}


    /**
     * Get recommendation score for a place
     */
    public function scorePlace($userId, $placeCategory, $lat, $lng)
    {
        $preferences = UserPreference::where('user_id', $userId)->first();
        
        if (!$preferences) {
            return 50; // Default
        }

        // Map category to preference field
        $fieldName = "preference_" . strtolower(str_replace(' ', '_', $placeCategory));
        
        return $preferences->$fieldName ?? 50;
    }
}
