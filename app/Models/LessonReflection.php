<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonReflection extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'user_id',
        'content',
        'submitted_at',
        'review_status',
        'mentor_note',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
