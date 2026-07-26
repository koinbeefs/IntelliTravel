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
     * @param bool $alternatives Whether to request alternative routes from OSRM
     */
    public function calculateRoute($waypoints, $mode = 'car', $alternatives = true)
    {
        if (count($waypoints) < 2) return null;

        if ($mode === 'transit') {
            $transitRoute = $this->calculateGeoapifyRoute($waypoints, 'transit');
            if ($transitRoute !== null) {
                return $transitRoute;
            }
            Log::info("Falling back to simulated OSRM transit route");
        }

        $profile = match($mode) {
            'bicycle', 'bike' => 'cycling',
            'walk', 'foot' => 'foot',
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
                'annotations' => 'true',
                'alternatives' => $alternatives ? 'true' : 'false'
            ]);

            if ($response->failed() || !isset($response['routes'][0])) {
                Log::warning("OSRM Route Failed: " . $response->body());
                return $this->handleOsrmFailure($waypoints, $mode);
            }

            $data = $response->json();

            // OSRM public demo server defaults all profiles to 'driving' speed/duration.
            // We override the duration dynamically based on the requested travel mode
            // to ensure accurate travel times are returned to the frontend.
            if (isset($data['routes'])) {
                foreach ($data['routes'] as &$r) {
                    $distance = $r['distance'] ?? 0; // in meters
                    
                    // Choose realistic speed divisor (meters per second)
                    $speed = null;
                    if ($mode === 'walk' || $mode === 'foot') {
                        $speed = 1.39; // Walking speed (~5 km/h)
                    } elseif ($mode === 'bicycle' || $mode === 'bike') {
                        $speed = 4.44; // Cycling speed (~16 km/h)
                    } elseif ($mode === 'transit') {
                        $speed = 6.11; // Transit speed (~22 km/h)
                    }
                    
                    if ($speed !== null) {
                        $r['duration'] = $distance / $speed;
                        if (isset($r['legs'])) {
                            foreach ($r['legs'] as &$leg) {
                                $legDistance = $leg['distance'] ?? $distance;
                                $leg['duration'] = $legDistance / $speed;
                                if (isset($leg['steps'])) {
                                    foreach ($leg['steps'] as &$step) {
                                        $stepDistance = $step['distance'] ?? 0;
                                        $step['duration'] = $stepDistance / $speed;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Fetch Toll-Free Alternative from Geoapify if mode is car
            if ($mode === 'car' && $alternatives) {
                $tollFreeRoute = $this->calculateGeoapifyRoute($waypoints, 'drive', 'tolls');
                if ($tollFreeRoute && isset($tollFreeRoute['routes'][0])) {
                    // Tag it so the frontend knows it's the toll-free alternative
                    $tollFree = $tollFreeRoute['routes'][0];
                    $tollFree['is_toll_free'] = true;
                    // Prepend or append to alternatives (OSRM routes[0] is primary)
                    if (isset($data['routes'])) {
                        $data['routes'][] = $tollFree;
                    } else {
                        $data['routes'] = [$tollFree];
                    }
                }
            }

            return $data;

        } catch (\Exception $e) {
            Log::error("Route Calculation Exception: " . $e->getMessage());
            return $this->handleOsrmFailure($waypoints, $mode);
        }
    }

    protected function handleOsrmFailure($waypoints, $mode) {
        $geoapifyMode = match($mode) {
            'bicycle', 'bike' => 'bicycle',
            'walk', 'foot' => 'walk',
            default => 'drive'
        };
        $fallback = $this->calculateGeoapifyRoute($waypoints, $geoapifyMode);
        if ($fallback && isset($fallback['routes'][0])) {
            return $fallback;
        }

        // Ultimate fallback: straight line
        return $this->generateFallbackStraightLine($waypoints, $mode);
    }

    protected function generateFallbackStraightLine($waypoints, $mode) {
        $distance = 0;
        for ($i = 0; $i < count($waypoints) - 1; $i++) {
            $distance += $this->haversineDistance($waypoints[$i], $waypoints[$i+1]);
        }
        $speed = match($mode) {
            'walk', 'foot' => 1.39,
            'bicycle', 'bike' => 4.44,
            'transit' => 6.11,
            default => 13.89
        };
        $duration = $distance / $speed;
        $coords = array_map(fn($w) => [$w[1], $w[0]], $waypoints);

        return [
            'routes' => [[
                'distance' => $distance,
                'duration' => $duration,
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $coords
                ],
                'legs' => [[
                    'distance' => $distance,
                    'duration' => $duration,
                    'steps' => [
                        [
                            'distance' => $distance,
                            'duration' => $duration,
                            'maneuver' => [
                                'type' => 'depart',
                                'location' => [$waypoints[0][1], $waypoints[0][0]]
                            ],
                            'name' => 'Straight line fallback'
                        ],
                        [
                            'distance' => 0,
                            'duration' => 0,
                            'maneuver' => [
                                'type' => 'arrive',
                                'location' => [$waypoints[count($waypoints)-1][1], $waypoints[count($waypoints)-1][0]]
                            ],
                            'name' => ''
                        ]
                    ]
                ]]
            ]]
        ];
    }

    protected function haversineDistance($a, $b) {
        $R = 6371000;
        $dLat = deg2rad($b[0] - $a[0]);
        $dLon = deg2rad($b[1] - $a[1]);
        $x = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($a[0])) * cos(deg2rad($b[0])) * sin($dLon/2) * sin($dLon/2);
        return 2 * $R * asin(sqrt($x));
    }

    /**
     * Fetch and format route from Geoapify to match OSRM structure.
     */
    protected function calculateGeoapifyRoute($waypoints, $mode = 'transit', $avoid = null)
    {
        $apiKey = env('GEOAPIFY_KEY');
        if (!$apiKey) {
            Log::error("GEOAPIFY_KEY is missing from environment variables.");
            return null;
        }

        $coordString = implode('|', array_map(fn($wp) => "{$wp[0]},{$wp[1]}", $waypoints));
        $url = "https://api.geoapify.com/v1/routing?waypoints={$coordString}&mode={$mode}&apiKey={$apiKey}";
        
        if ($avoid) {
            $url .= "&avoid={$avoid}";
        }

        try {
            $response = Http::get($url);

            if ($response->failed() || !isset($response['features'][0])) {
                Log::warning("Geoapify Transit Failed: " . $response->body());
                return null;
            }

            $feature = $response['features'][0];
            $props = $feature['properties'];
            $distance = $props['distance'] ?? 0;
            $duration = $props['time'] ?? 0;

            // Extract flat coordinates
            $flatCoords = [];
            if (isset($feature['geometry']['type']) && $feature['geometry']['type'] === 'MultiLineString') {
                foreach ($feature['geometry']['coordinates'] as $line) {
                    foreach ($line as $pt) {
                        $flatCoords[] = $pt;
                    }
                }
            } else {
                $flatCoords = $feature['geometry']['coordinates'] ?? [];
            }

            // Map Geoapify steps to OSRM format
            $osrmSteps = [];
            if (isset($props['legs'])) {
                foreach ($props['legs'] as $leg) {
                    if (isset($leg['steps'])) {
                        foreach ($leg['steps'] as $step) {
                            $fromIdx = $step['from_index'] ?? 0;
                            // Geoapify returns [lng, lat], just like OSRM GeoJSON geometry
                            $location = $flatCoords[$fromIdx] ?? [0, 0];
                            
                            $osrmSteps[] = [
                                'distance' => $step['distance'] ?? 0,
                                'duration' => $step['time'] ?? 0,
                                'name' => $step['instruction']['text'] ?? '',
                                'maneuver' => [
                                    'type' => 'turn',
                                    'modifier' => '',
                                    'location' => $location
                                ]
                            ];
                        }
                    }
                }
            }

            // Return mock OSRM format
            return [
                'routes' => [
                    [
                        'distance' => $distance,
                        'duration' => $duration,
                        'geometry' => [
                            'coordinates' => $flatCoords
                        ],
                        'legs' => [
                            [
                                'distance' => $distance,
                                'duration' => $duration,
                                'steps' => $osrmSteps
                            ]
                        ]
                    ]
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Geoapify Route Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get alternative routes (Used for "Calculate Route" feature)
     */
    public function getRouteAlternatives($waypoints)
    {
        $route = $this->calculateRoute($waypoints, 'car', true);
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
