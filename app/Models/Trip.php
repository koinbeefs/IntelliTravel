<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'user_id', 'title', 'destination', 'description', 'trip_type',
        'transit_type', 'start_date', 'end_date', 'center_lat', 'center_lng',
        'route_data', 'is_published', 'is_active'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'route_data' => 'array', // Important for JSON handling
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsToMany(User::class, 'trip_user')
                ->withPivot('role', 'status')
                ->withTimestamps();
    }
    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function itineraries()
    {
        return $this->hasMany(Itinerary::class)->orderBy('day_number')->orderBy('order');
    }
}
