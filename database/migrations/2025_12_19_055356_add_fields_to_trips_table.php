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
        Schema::table('trips', function (Blueprint $table) {
            // Add new columns (if not exists)
            if (!Schema::hasColumn('trips', 'destination')) {
                $table->string('destination')->nullable(); // "Baguio, Benguet"
            }
            if (!Schema::hasColumn('trips', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('trips', 'trip_type')) {
                $table->enum('trip_type', ['manual', 'automatic'])->default('manual');
            }
            if (!Schema::hasColumn('trips', 'transit_type')) {
                $table->string('transit_type')->default('car'); // car, bike, walk, bus
            }
            if (!Schema::hasColumn('trips', 'center_lat')) {
                $table->double('center_lat')->nullable(); // Center of trip for recommendations
            }
            if (!Schema::hasColumn('trips', 'center_lng')) {
                $table->double('center_lng')->nullable();
            }
            if (!Schema::hasColumn('trips', 'route_data')) {
                $table->json('route_data')->nullable(); // Full multi-stop route geometry
            }
            if (!Schema::hasColumn('trips', 'is_published')) {
                $table->boolean('is_published')->default(false);
            }
            if (!Schema::hasColumn('trips', 'is_active')) {
                $table->boolean('is_active')->default(false); // Currently on this trip?
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            //
        });
    }
};
