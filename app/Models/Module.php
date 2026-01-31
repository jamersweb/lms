<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'slug', 'sort_order'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    /**
     * Get the content rule for this module.
     */
    public function contentRule(): MorphOne
    {
        return $this->morphOne(ContentRule::class, 'ruleable');
    }

    /**
     * Get the task for this module.
     */
    public function task(): MorphOne
    {
        return $this->morphOne(Task::class, 'taskable');
    }
}
