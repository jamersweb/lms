<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sunnah_assessment_id',
        'question_key',
        'already_practicing',
        'notes',
    ];

    protected $casts = [
        'already_practicing' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessment()
    {
        return $this->belongsTo(SunnahAssessment::class, 'sunnah_assessment_id');
    }
}
