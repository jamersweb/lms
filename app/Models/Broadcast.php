<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'audience_json',
        'subject',
        'body',
        'sent_at',
    ];

    protected $casts = [
        'audience_json' => 'array',
        'sent_at' => 'datetime',
    ];
}
