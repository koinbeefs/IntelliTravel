<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallSignal extends Model
{
    protected $fillable = [
        'conversation_id', 'from_id', 'to_id', 'payload'
    ];

    protected $casts = [
        'payload' => 'array'
    ];
}
