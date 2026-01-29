<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'instructor', 'level', 'thumbnail', 'sort_order'];

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    public function assessment()
    {
        return $this->hasOne(SunnahAssessment::class)->where('is_active', true);
    }

    public function exemptions()
    {
        return $this->hasMany(CourseExemption::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }
}
