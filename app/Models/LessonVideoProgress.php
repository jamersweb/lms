<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonVideoProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'provider',
        'duration_seconds',
        'last_position_seconds',
        'percent_complete',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'last_position_seconds' => 'integer',
        'percent_complete' => 'decimal:2',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get video progress for a specific user and lesson.
     */
    public static function forUserAndLesson($userId, $lessonId)
    {
        return static::where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->first();
    }
}
