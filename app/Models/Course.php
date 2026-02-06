<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'description', 'instructor', 'level', 'thumbnail', 'sort_order'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = static::generateUniqueSlug($course->title);
            }
        });

        static::updating(function ($course) {
            // Regenerate slug if title changed and slug wasn't manually set
            if ($course->isDirty('title') && !$course->isDirty('slug')) {
                $course->slug = static::generateUniqueSlug($course->title, $course->id);
            }
        });
    }

    /**
     * Generate a unique slug from title.
     */
    protected static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    /**
     * Get the content rule for this course.
     */
    public function contentRule(): MorphOne
    {
        return $this->morphOne(ContentRule::class, 'ruleable');
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
