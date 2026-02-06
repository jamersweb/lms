<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'slug', 'sort_order'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($module) {
            if (empty($module->slug)) {
                $module->slug = static::generateUniqueSlug($module->title, $module->course_id);
            }
        });

        static::updating(function ($module) {
            // Regenerate slug if title changed and slug wasn't manually set
            if ($module->isDirty('title') && !$module->isDirty('slug')) {
                $module->slug = static::generateUniqueSlug($module->title, $module->course_id, $module->id);
            }
            // If course_id changed, regenerate slug to ensure uniqueness within new course
            if ($module->isDirty('course_id') && !$module->isDirty('slug')) {
                $module->slug = static::generateUniqueSlug($module->title, $module->course_id, $module->id);
            }
        });
    }

    /**
     * Generate a unique slug from title within a course.
     */
    protected static function generateUniqueSlug(string $title, int $courseId, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->where('course_id', $courseId)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

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
