<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'trip_id', 'user_id', 'place_id', 'place_name', 'place_address',
        'lat', 'lng', 'day_number', 'order', 'time', 'notes',
        'duration_minutes', 'weather_summary', 'weather_icon',
        'distance_from_previous', 'drive_time_from_previous', 'speed_limit',
        'nearby_gas_stations', 'is_recommended', 'recommendation_score'
    ];

    protected $casts = [
        'nearby_gas_stations' => 'array', // Automatically handles json_encode/decode
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function visit()
    {
        return $this->hasOne(TripVisit::class);
    }
}
