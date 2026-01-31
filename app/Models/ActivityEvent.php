<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityEvent extends Model
{
    use HasFactory;

    // Event types
    const TYPE_LESSON_WATCH_STARTED = 'lesson_watch_started';
    const TYPE_LESSON_WATCH_COMPLETED = 'lesson_watch_completed';
    const TYPE_LESSON_WATCH_VIOLATION = 'lesson_watch_violation';
    const TYPE_LESSON_REFLECTION_SUBMITTED = 'lesson_reflection_submitted';
    const TYPE_TASK_CHECKIN_COMPLETED = 'task_checkin_completed';
    const TYPE_LESSON_UNLOCKED = 'lesson_unlocked';
    const TYPE_BROADCAST_SENT = 'broadcast_sent';
    const TYPE_BROADCAST_OPENED = 'broadcast_opened';
    const TYPE_DUA_POSTED = 'dua_posted';
    const TYPE_DUA_PRAYED = 'dua_prayed';
    const TYPE_QUESTION_CREATED = 'question_created';
    const TYPE_QUESTION_REPLIED = 'question_replied';

    protected $fillable = [
        'user_id',
        'event_type',
        'subject_type',
        'subject_id',
        'course_id',
        'module_id',
        'lesson_id',
        'meta',
        'ip',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function subject()
    {
        return $this->morphTo('subject', 'subject_type', 'subject_id');
    }
}
