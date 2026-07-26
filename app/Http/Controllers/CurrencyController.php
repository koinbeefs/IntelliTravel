<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyController extends Controller
{
    /**
     * Get the latest exchange rates for a base currency.
     * Caches the results for 1 hour to avoid hitting API limits.
     */
    public function latest(Request $request)
    {
        $base = $request->query('base', 'USD');
        
        $cacheKey = "exchange_rates_{$base}";
        
        $rates = Cache::remember($cacheKey, 3600, function () use ($base) {
            $response = Http::withHeaders([
                'x-rapidapi-key' => env('RAPIDAPI_KEY'),
                'x-rapidapi-host' => 'exchangerate-api.p.rapidapi.com'
            ])->get("https://exchangerate-api.p.rapidapi.com/rapid/latest/{$base}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        });

        if ($rates) {
            return response()->json($rates);
        }

        return response()->json(['error' => 'Failed to fetch rates'], 500);
    }
}
