<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaceService
{
    protected $geoapifyKey;
    protected $rapidApiKey;

    public function __construct()
    {
        $this->geoapifyKey = env('GEOAPIFY_KEY');
        $this->rapidApiKey = env('RAPIDAPI_KEY');
    }

    public function search($lat, $lng, $category = null, $query = null)
    {
        if (!empty($query) && strlen($query) > 1) {
            return $this->searchGeoapifyText($lat, $lng, $query);
        }
        if (!empty($category)) {
            return $this->searchGeoapifyCategory($lat, $lng, $category);
        }
        return [];
    }

    public function getPopular($lat, $lng)
    {
        $places = $this->fetchFromRapidApi($lat, $lng, 'tourist_attraction', 'prominence');
        if (empty($places)) {
            Log::info("RapidAPI empty/failed, falling back to Geoapify for Popular");
            return $this->searchGeoapifyCategory($lat, $lng, 'attractions');
        }
        return $places;
    }

    public function getHighRated($lat, $lng)
    {
        $places = $this->fetchFromRapidApi($lat, $lng, 'restaurant', 'prominence');
        if (empty($places)) {
             return $this->searchGeoapifyCategory($lat, $lng, 'restaurant');
        }

        // Filter > 4.0
        $filtered = array_filter($places, fn($p) => ($p['rating'] ?? 0) >= 4.0);

        // FIX: array_values() converts sparse array back to a list
        return array_values($filtered); 
    }

    // --- INTERNAL HELPER METHODS ---

    private function fetchFromRapidApi($lat, $lng, $type, $rankby)
    {
        $url = 'https://google-map-places.p.rapidapi.com/maps/api/place/nearbysearch/json';
        
        try {
            // Log that we are trying RapidAPI
            Log::info("Trying RapidAPI for {$type} at {$lat},{$lng}");

            $response = Http::withHeaders([
                'x-rapidapi-host' => 'google-map-places.p.rapidapi.com',
                'x-rapidapi-key' => $this->rapidApiKey
            ])->withoutVerifying()->get($url, [
                'location' => "$lat,$lng",
                'radius' => 5000,
                'language' => 'en',
                'type' => $type,
                'opennow' => 'true',
                'rankby' => $rankby,
            ]);

            if ($response->failed()) {
                Log::error("RapidAPI HTTP Error: " . $response->body());
                return [];
            }

            $results = $response->json()['results'] ?? [];
            
            return array_map(function ($place) {
                return [
                    'id' => $place['place_id'],
                    'name' => $place['name'],
                    'lat' => $place['geometry']['location']['lat'],
                    'lng' => $place['geometry']['location']['lng'],
                    'address' => $place['vicinity'] ?? '',
                    'category' => 'google_place',
                    'rating' => $place['rating'] ?? null,
                    'user_ratings_total' => $place['user_ratings_total'] ?? 0,
                    'reviews' => $place['user_ratings_total'] ?? 0,
                    'photo_reference' => $place['photos'][0]['photo_reference'] ?? null,
                    'source' => 'google'
                ];
            }, $results);

        } catch (\Exception $e) {
            Log::error("RapidAPI Exception: " . $e->getMessage());
            return [];
        }
    }

    private function searchGeoapifyCategory($lat, $lng, $category)
    {
        $categoryMap = [
            'restaurant' => 'catering',
            'hotel' => 'accommodation',
            'shopping' => 'commercial',
            'hospitals' => 'healthcare',
            'banks' => 'service.financial',
            'attractions' => 'tourism',
            'coffee' => 'catering.cafe',
            'gas' => 'commercial.gas'
        ];

        $geoCategory = $categoryMap[strtolower($category)] ?? 'tourism';
        $url = "https://api.geoapify.com/v2/places?categories={$geoCategory}&filter=circle:{$lng},{$lat},10000&limit=20&apiKey={$this->geoapifyKey}";

        try {
            $response = Http::withoutVerifying()->get($url);
            if ($response->failed()) {
                Log::error("Geoapify Error: " . $response->body());
                return [];
            }
            return $this->formatGeoapify($response->json()['features'] ?? []);
        } catch (\Exception $e) {
            Log::error("Geoapify Exception: " . $e->getMessage());
            return [];
        }
    }

    private function searchGeoapifyText($lat, $lng, $query)
    {
        $url = "https://api.geoapify.com/v2/places?text=" . urlencode($query) . "&filter=circle:{$lng},{$lat},10000&limit=20&apiKey={$this->geoapifyKey}";
        
        try {
            $response = Http::withoutVerifying()->get($url);
            if ($response->failed()) return [];
            return $this->formatGeoapify($response->json()['features'] ?? []);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function formatGeoapify($features)
    {
        return array_map(function ($feature) {
            $props = $feature['properties'];
            return [
                'id' => $props['place_id'] ?? uniqid(),
                'name' => $props['name'] ?? 'Unnamed Place',
                'lat' => $feature['geometry']['coordinates'][1],
                'lng' => $feature['geometry']['coordinates'][0],
                'address' => $props['address_line2'] ?? $props['formatted'] ?? '',
                'category' => explode('.', $props['categories'][0] ?? 'unknown')[0],
                'rating' => null,
                'reviews' => 0,
                'photo_reference' => null,
                'source' => 'geoapify'
            ];
        }, $features);
    }

    public function reverseGeocode($lat, $lng)
    {
        $url = "https://api.geoapify.com/v1/geocode/reverse?lat={$lat}&lon={$lng}&apiKey={$this->geoapifyKey}";
        
        try {
            $response = Http::withoutVerifying()->get($url);
            if ($response->failed()) return "Unknown Location";
            
            $props = $response->json()['features'][0]['properties'] ?? [];
            
            // Return City, Country (e.g., "Manila, Philippines")
            $city = $props['city'] ?? $props['town'] ?? $props['village'] ?? 'Unknown';
            $country = $props['country'] ?? '';
            
            return "{$city}, {$country}";
        } catch (\Exception $e) {
            return "Unknown Location";
        }
    }

    public function getPhoto($photoReference)
    {
        // Google Places Photo API via RapidAPI
        $url = 'https://google-map-places.p.rapidapi.com/maps/api/place/photo';

        try {
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'google-map-places.p.rapidapi.com',
                'x-rapidapi-key' => $this->rapidApiKey
            ])->withoutVerifying()->get($url, [
                'maxwidth' => 400, // Limit size to save bandwidth
                'photoreference' => $photoReference,
            ]);

            if ($response->failed()) {
                return redirect('https://placehold.co/400x300?text=No+Image');
            }

            // Return the image data with the correct content type
            return response($response->body())
                ->header('Content-Type', $response->header('Content-Type') ?? 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=86400'); // Cache for 24 hours

        } catch (\Exception $e) {
            return redirect('https://placehold.co/400x300?text=Error');
        }
    }

    public function autocomplete($query, $lat, $lng)
    {
        // Use Geoapify Autocomplete API
        $url = "https://api.geoapify.com/v1/geocode/autocomplete?text=" . urlencode($query) . "&bias=proximity:{$lng},{$lat}&limit=5&apiKey={$this->geoapifyKey}";
        
        try {
            $response = Http::withoutVerifying()->get($url);
            if ($response->failed()) return [];
            
            $features = $response->json()['features'] ?? [];
            return array_map(function($f) {
                $p = $f['properties'];
                return [
                    'id' => $p['place_id'] ?? uniqid(),
                    'name' => $p['name'] ?? $p['formatted'], // Sometimes name is empty, use formatted
                    'address' => $p['address_line2'] ?? $p['city'] ?? '',
                    'lat' => $p['lat'],
                    'lng' => $p['lon']
                ];
            }, $features);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getRecommended($lat, $lng, $userId)
    {
        // 1. Get User's Last Interaction Category
        $lastInteraction = \App\Models\UserInteraction::where('user_id', $userId)
            ->latest()
            ->first();

        // 2. Determine Preference (Default to 'attractions' if new user)
        $category = $lastInteraction ? $lastInteraction->category : 'attractions';

        Log::info("Recommending category: {$category} for User {$userId}");

        // 3. Fetch from Geoapify (or RapidAPI if you prefer)
        return $this->searchGeoapifyCategory($lat, $lng, $category);
    }
}
