<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteService
{
    protected $osrmBaseUrl = 'https://router.project-osrm.org';

    /**
     * Calculate route for multiple waypoints
     * @param array $waypoints Array of [lat, lng] arrays
     * @param string $mode 'car', 'bike', 'walk', etc.
     */
    public function calculateRoute($waypoints, $mode = 'car')
    {
        if (count($waypoints) < 2) return null;

        // 1. Map Transit Type to OSRM Profile
        $profile = match($mode) {
            'bicycle', 'bike' => 'cycling',
            'walk', 'foot' => 'walking',
            default => 'driving'
        };

        // 2. Format Coordinates: "lng,lat;lng,lat;..."
        $coordString = implode(';', array_map(fn($wp) => "{$wp[1]},{$wp[0]}", $waypoints));
        
        $url = "{$this->osrmBaseUrl}/route/v1/{$profile}/{$coordString}";

        try {
            $response = Http::get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
                'steps' => 'true',
                'annotations' => 'true'
            ]);

            if ($response->failed() || !isset($response['routes'][0])) {
                Log::warning("OSRM Route Failed: " . $response->body());
                return null;
            }

            // Return the raw OSRM response structure expected by Controller
            return $response->json();

        } catch (\Exception $e) {
            Log::error("Route Calculation Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get alternative routes (Used for "Calculate Route" feature)
     */
    public function getRouteAlternatives($waypoints)
    {
        // For OSRM free tier, alternatives are just the main route request with alternatives=true
        // But for simplicity/stability, we reuse the main calculation logic
        $route = $this->calculateRoute($waypoints, 'car');
        return $route ? $route['routes'] : [];
    }

    /**
     * Extract Speed Limits from Route Data
     */
    public function getSpeedLimits($routeData)
    {
        // OSRM annotations for maxspeed are complex. 
        // We will simulate a simplified list based on steps for the UI.
        
        $limits = [];
        $steps = $routeData['legs'][0]['steps'] ?? [];

        foreach ($steps as $step) {
            $name = $step['name'] ?? 'Unknown Road';
            if (empty($name)) continue;

            // Simple heuristic since free OSRM rarely returns maxspeed data
            // Highway/Way/Ave usually faster
            $speed = 40;
            if (stripos($name, 'Highway') !== false || stripos($name, 'Expressway') !== false) $speed = 80;
            elseif (stripos($name, 'Avenue') !== false || stripos($name, 'Road') !== false) $speed = 60;

            // De-duplicate
            if (!isset($limits[$name])) {
                $limits[$name] = [
                    'name' => $name,
                    'max_speed' => $speed
                ];
            }
        }

        return array_values($limits);
    }
}
