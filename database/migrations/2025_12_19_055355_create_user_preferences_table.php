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
        Schema::create('user_preferences', function (Blueprint $table) {
           $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Category preferences (0-100 score)
            $table->integer('preference_restaurant')->default(50);
            $table->integer('preference_hotel')->default(50);
            $table->integer('preference_shopping')->default(50);
            $table->integer('preference_coffee')->default(50);
            $table->integer('preference_attractions')->default(50);
            $table->integer('preference_nature')->default(50);
            $table->integer('preference_culture')->default(50);
            $table->integer('preference_adventure')->default(50);
            
            // Transit preferences
            $table->string('preferred_transit')->default('car'); // car, bike, walk, bus
            $table->boolean('prefer_main_roads')->default(true);
            $table->boolean('prefer_scenic_routes')->default(false);
            $table->integer('avg_hours_per_stop')->default(2); // How long user typically spends at places
            
            // Trip preferences
            $table->integer('avg_trip_duration')->default(3); // days
            $table->time('preferred_start_time')->default('09:00'); // when they like to start
            $table->time('preferred_end_time')->default('18:00');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
