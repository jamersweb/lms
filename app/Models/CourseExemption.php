<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseExemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'sunnah_assessment_id',
        'exempted_modules',
        'reason',
    ];

    protected $casts = [
        'exempted_modules' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function assessment()
    {
        return $this->belongsTo(SunnahAssessment::class);
    }
}
