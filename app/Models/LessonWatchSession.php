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
        'watch_time_seconds',
        'last_time_seconds',
        'seek_events_count',
        'max_playback_rate',
        'is_valid',
        'invalid_reason',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_valid' => 'boolean',
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

