<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Import Sanctum

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'google_id',
        'profile_pic',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_active_at' => 'datetime',
        ];
    }

    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function visits()
    {
        return $this->hasMany(TripVisit::class);
    }
    public function sharedTrips()
    {
        return $this->belongsToMany(Trip::class, 'trip_user');
    }

    public function calculateStats()
    {
        $tripsCount = \App\Models\Trip::where('user_id', $this->id)->count();
        $reviewsCount = \App\Models\Review::where('user_id', $this->id)->count();
        $savedCount = \App\Models\UserInteraction::where('user_id', $this->id)->count();
        $totalDistanceMeters = \App\Models\Itinerary::where('user_id', $this->id)->sum('distance_from_previous');
        $totalDistanceKm = round($totalDistanceMeters / 1000);
        $citiesCount = \App\Models\TripVisit::where('user_id', $this->id)->distinct('place_name')->count('place_name');
        $photosCount = 0; // Stub for now

        $totalXp = ($tripsCount * 150) + ($reviewsCount * 50) + ($savedCount * 10);
        $level = floor($totalXp / 1000) + 1;
        $currentXp = $totalXp % 1000;
        $nextLevelXp = 1000;

        return [
            'trips' => $tripsCount,
            'reviews' => $reviewsCount,
            'saved' => $savedCount,
            'total_distance_km' => $totalDistanceKm,
            'cities' => $citiesCount,
            'photos' => $photosCount,
            'level' => $level,
            'current_xp' => $currentXp,
            'next_level_xp' => $nextLevelXp,
            'total_xp' => $totalXp,
        ];
    }
}
