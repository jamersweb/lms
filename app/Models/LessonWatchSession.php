<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonWatchSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'started_at',
        'ended_at',
        'watched_seconds',
        'last_position_seconds',
        'seek_attempts',
        'max_playback_rate',
        'violations',
        'is_valid',
        'invalid_reason',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_valid' => 'boolean',
        'violations' => 'array',
        'max_playback_rate' => 'decimal:1',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}

