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
        Schema::create('trips', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('title');
        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();
        $table->json('route_data')->nullable();
        $table->timestamps();
    });

    // 2. Create Itineraries SECOND
    Schema::create('itineraries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('trip_id')->constrained()->onDelete('cascade'); // Now this works because 'trips' exists
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('place_id');
        $table->string('place_name');
        $table->string('place_address')->nullable();
        $table->double('lat');
        $table->double('lng');
        $table->integer('day_number')->default(1);
        $table->integer('order')->default(0);
        $table->time('time')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itineraries');
    }
};
