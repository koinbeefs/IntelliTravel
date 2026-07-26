<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'trip_invite', 'trip_update', 'message', 'expense', 'system'
            $table->string('title');
            $table->text('message');
            $table->string('icon')->default('bell'); // 'map', 'dollar', 'users', 'star', 'alert', 'message', 'route', 'zap', 'gift', 'bell'
            $table->boolean('read')->default(false);
            $table->json('data')->nullable(); // Additional data like trip_id, action_url, etc.
            $table->string('action_label')->nullable(); // Label for action button (e.g., 'Accept', 'Decline')
            $table->string('action_type')->nullable(); // 'accept_invite', 'decline_invite', 'view_trip', etc.
            $table->timestamps();
            
            $table->index(['user_id', 'read']);
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
