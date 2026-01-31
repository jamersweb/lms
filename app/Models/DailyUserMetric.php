<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyUserMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'active_seconds',
        'watched_seconds',
        'lessons_completed',
        'reflections_submitted',
        'task_checkins',
        'violations_count',
        'seek_attempts',
        'max_playback_rate',
        'stagnation_score',
        'last_activity_at',
    ];

    protected $casts = [
        'date' => 'date',
        'max_playback_rate' => 'decimal:1',
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
