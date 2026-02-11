<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonQuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'question_text',
        'options',
        'correct_index',
        'correct_indices',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_index' => 'integer',
        'correct_indices' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Get the list of correct option indices (supports multiple correct answers).
     */
    public function getCorrectIndices(): array
    {
        if (! empty($this->correct_indices)) {
            return array_values(array_map('intval', $this->correct_indices));
        }
        if ($this->correct_index !== null) {
            return [(int) $this->correct_index];
        }
        return [];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
