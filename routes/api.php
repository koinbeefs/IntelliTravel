<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\TripInvitationController;
use App\Http\Controllers\ChatController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/google', [AuthController::class, 'googleRedirect']);
Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/interactions', [InteractionController::class, 'store']);
    
    // Trips
    Route::post('trips/{id}/activate', [TripController::class, 'activate']);
    Route::get('trips/{id}/recommendations', [TripController::class, 'getRecommendations']);
    Route::post('/trips/{id}/invite', [TripInvitationController::class, 'invite']);
    Route::get('/trips/{id}/collaborators', [TripInvitationController::class, 'collaborators']);
    
    Route::get('/trips/{id}/messages', [ChatController::class, 'index']);
    Route::post('/trips/{id}/messages', [ChatController::class, 'store']);
    Route::resource('trips', TripController::class); // Generic last
    
    // Itineraries - SPECIFIC ROUTES MUST BE FIRST
    Route::get('itineraries/search-places', [ItineraryController::class, 'searchPlaces']); // <--- MOVED UP
    Route::get('itineraries/suggest-places', [ItineraryController::class, 'suggestPlaces']); // <--- MOVED UP
    Route::get('trips/{tripId}/itineraries', [ItineraryController::class, 'byTrip']);
    Route::post('trips/{tripId}/calculate-route', [ItineraryController::class, 'calculateRoute']);
    Route::get('trips/{tripId}/route-details', [ItineraryController::class, 'routeDetails']);
    
    // Itineraries - GENERIC RESOURCE LAST
    Route::resource('itineraries', ItineraryController::class);
    // Remove the explicit 'show' route if resource already covers it, or keep it if resource is partial. 
    // Since your controller doesn't have 'show', standard 'resource' will try to call it and fail if you hit /itineraries/{id}.
    // If you don't implement show, use: Route::resource('itineraries', ItineraryController::class)->except(['show']);

    // User Preferences
    Route::get('preferences', [UserPreferenceController::class, 'show']);
    Route::put('preferences', [UserPreferenceController::class, 'update']);
    Route::post('preferences/analyze', [UserPreferenceController::class, 'analyzeHistory']);
    
    // Places
    Route::get('/places/recommended', [PlaceController::class, 'recommended']);
    Route::get('/places/popular', [PlaceController::class, 'popular']);
    Route::get('/places/high-rated', [PlaceController::class, 'highRated']);
    Route::get('/places/reverse', [PlaceController::class, 'reverseGeocode']);
    Route::get('/places/autocomplete', [PlaceController::class, 'autocomplete']);
    Route::get('/places/search', [PlaceController::class, 'search']);
    Route::get('/places/photo', [PlaceController::class, 'photo']);
});
