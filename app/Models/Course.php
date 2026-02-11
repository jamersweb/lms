<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'instructor',
        'level',
        'thumbnail',
        'sort_order',
        'title_en',
        'title_en_roman',
        'title_ur',
        'description_en',
        'description_en_roman',
        'description_ur',
    ];

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

    /**
     * Get title in preferred content locale with graceful fallback.
     */
    public function getLocalizedTitle(string $locale): string
    {
        $field = match ($locale) {
            'en_roman' => 'title_en_roman',
            'ur' => 'title_ur',
            default => 'title_en',
        };

        if (!empty($this->{$field})) {
            return $this->{$field};
        }

        // Fallbacks
        if (!empty($this->title_en)) {
            return $this->title_en;
        }

        return $this->title ?? '';
    }

    /**
     * Get description in preferred content locale with graceful fallback.
     */
    public function getLocalizedDescription(string $locale): ?string
    {
        $field = match ($locale) {
            'en_roman' => 'description_en_roman',
            'ur' => 'description_ur',
            default => 'description_en',
        };

        if (!empty($this->{$field})) {
            return $this->{$field};
        }

        if (!empty($this->description_en)) {
            return $this->description_en;
        }

        return $this->description;
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
