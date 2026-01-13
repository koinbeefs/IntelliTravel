<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
           if (!Schema::hasColumn('itineraries', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(120); // How long to spend here
            }
            if (!Schema::hasColumn('itineraries', 'weather_summary')) {
                $table->string('weather_summary')->nullable(); // "Sunny, 28°C"
            }
            if (!Schema::hasColumn('itineraries', 'weather_icon')) {
                $table->string('weather_icon')->nullable(); // Weather emoji/icon code
            }
            if (!Schema::hasColumn('itineraries', 'distance_from_previous')) {
                $table->double('distance_from_previous')->nullable(); // in km
            }
            if (!Schema::hasColumn('itineraries', 'drive_time_from_previous')) {
                $table->integer('drive_time_from_previous')->nullable(); // in minutes
            }
            if (!Schema::hasColumn('itineraries', 'speed_limit')) {
                $table->integer('speed_limit')->nullable(); // km/h
            }
            if (!Schema::hasColumn('itineraries', 'nearby_gas_stations')) {
                $table->json('nearby_gas_stations')->nullable(); // Array of gas stations
            }
            if (!Schema::hasColumn('itineraries', 'is_recommended')) {
                $table->boolean('is_recommended')->default(false); // Was this auto-suggested?
            }
            if (!Schema::hasColumn('itineraries', 'recommendation_score')) {
                $table->double('recommendation_score')->nullable(); // 0-100
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            //
        });
    }
};
