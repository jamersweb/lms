<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_id',
        'is_completed',
        'last_position_seconds',
        'completed_at',
        'time_watched_seconds',
        'last_heartbeat_at',
        'max_playback_rate_seen',
        'seek_detected',
        'verified_completion',
        'verified_at',
        'status',
        'available_at',
        'unlocked_at',
        'started_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'verified_at' => 'datetime',
        'available_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'started_at' => 'datetime',
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
