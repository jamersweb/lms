<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SunnahAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'questions',
        'is_active',
    ];

    protected $casts = [
        'questions' => 'array',
        'is_active' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function responses()
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    public function exemptions()
    {
        return $this->hasMany(CourseExemption::class);
    }
}
