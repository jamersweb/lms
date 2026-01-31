<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseProgressSnapshot extends Model
{
    use HasFactory;

    // Blocked by reasons
    const BLOCKED_REFLECTION_REQUIRED = 'reflection_required';
    const BLOCKED_TASK_INCOMPLETE = 'task_incomplete';
    const BLOCKED_NOT_RELEASED_YET = 'not_released_yet';
    const BLOCKED_PREVIOUS_LESSON_INCOMPLETE = 'previous_lesson_incomplete';
    const BLOCKED_NOT_NEXT_LESSON = 'not_next_lesson';

    protected $fillable = [
        'user_id',
        'course_id',
        'lessons_total',
        'lessons_completed',
        'reflections_required',
        'reflections_done',
        'tasks_required',
        'tasks_done',
        'next_lesson_id',
        'next_lesson_release_at',
        'blocked_by',
    ];

    protected $casts = [
        'next_lesson_release_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = false; // Only use updated_at

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function nextLesson()
    {
        return $this->belongsTo(Lesson::class, 'next_lesson_id');
    }
}
