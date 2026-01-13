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
        Schema::create('trip_visits', function (Blueprint $table) {
           $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('itinerary_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('place_id');
            $table->string('place_name');
            $table->string('place_category'); // restaurant, hotel, attraction, etc
            $table->double('lat');
            $table->double('lng');
            
            $table->integer('duration_minutes')->default(0); // How long they spent
            $table->integer('user_rating')->nullable(); // 1-5 stars
            $table->text('user_notes')->nullable();
            
            $table->timestamp('visited_at'); // When they actually visited
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_visits');
    }
};
