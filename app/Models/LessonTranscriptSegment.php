<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTranscriptSegment extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'start_seconds', 'end_seconds', 'text'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
