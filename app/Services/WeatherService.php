<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct()
    {
        $this->apiKey = env('OPENWEATHER_API_KEY');
    }

    /**
     * Get weather for a location
     */
    public function getWeather($lat, $lng)
    {
        try {
            $response = Http::get($this->baseUrl, [
                'lat' => $lat,
                'lon' => $lng,
                'appid' => $this->apiKey,
                'units' => 'metric' // Celsius
            ]);

            if ($response->failed()) return null;

            $data = $response->json();

            return [
                'temp' => $data['main']['temp'] ?? null,
                'feels_like' => $data['main']['feels_like'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'description' => $data['weather'][0]['main'] ?? 'Unknown',
                'icon' => $data['weather'][0]['icon'] ?? null,
                'wind_speed' => $data['wind']['speed'] ?? null,
                'rain_chance' => $data['clouds']['all'] ?? 0,
                'summary' => $this->formatWeatherSummary($data)
            ];
        } catch (\Exception $e) {
            Log::error("Weather API Error: " . $e->getMessage());
            return null;
        }
    }

    private function formatWeatherSummary($data)
    {
        $temp = $data['main']['temp'] ?? 0;
        $desc = $data['weather'][0]['main'] ?? 'Unknown';
        return "$desc, {$temp}°C";
    }
}
