<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_signals', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('conversation_id');
            $blueprint->unsignedBigInteger('from_id');
            $blueprint->unsignedBigInteger('to_id');
            $blueprint->json('payload');
            $blueprint->timestamps();

            $blueprint->index(['to_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_signals');
    }
};
