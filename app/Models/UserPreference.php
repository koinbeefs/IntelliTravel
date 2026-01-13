<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'preference_restaurant', 'preference_hotel', 'preference_shopping',
        'preference_coffee', 'preference_attractions', 'preference_nature',
        'preference_culture', 'preference_adventure',
        'preferred_transit', 'prefer_main_roads', 'prefer_scenic_routes',
        'avg_hours_per_stop', 'avg_trip_duration', 'preferred_start_time',
        'preferred_end_time'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
