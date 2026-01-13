<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripVisit extends Model
{
    protected $fillable = [
        'user_id', 'itinerary_id', 'place_id', 'place_name', 'place_category',
        'lat', 'lng', 'duration_minutes', 'user_rating', 'user_notes', 'visited_at'
    ];

    protected $dates = ['visited_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }
}
