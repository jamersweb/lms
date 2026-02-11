<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'sunnah_pointers',
        'sunnah_pointers_en',
        'sunnah_pointers_en_roman',
        'sunnah_pointers_ur',
        'duas_text',
        'duas_text_en',
        'duas_text_en_roman',
        'duas_text_ur',
        'audio_path',
        'pdf_path',
    ];

    /**
     * Get localized Sunnah pointers for given content locale.
     */
    public function getLocalizedSunnahPointers(string $locale): ?string
    {
        $field = match ($locale) {
            'en_roman' => 'sunnah_pointers_en_roman',
            'ur' => 'sunnah_pointers_ur',
            default => 'sunnah_pointers_en',
        };

        if (!empty($this->{$field})) {
            return $this->{$field};
        }

        return $this->sunnah_pointers ?? $this->sunnah_pointers_en ?? null;
    }

    /**
     * Get localized Duas text for given content locale.
     */
    public function getLocalizedDuasText(string $locale): ?string
    {
        $field = match ($locale) {
            'en_roman' => 'duas_text_en_roman',
            'ur' => 'duas_text_ur',
            default => 'duas_text_en',
        };

        if (!empty($this->{$field})) {
            return $this->{$field};
        }

        return $this->duas_text ?? $this->duas_text_en ?? null;
    }

    /**
     * Boot the model to clear PDF cache when resource is updated
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($resource) {
            // Clear PDF cache when resource is updated
            // Invalidate cache by updating the timestamp in cache key
            // The new timestamp will generate a new cache key
        });

        static::deleted(function ($resource) {
            // Clear PDF cache when resource is deleted
            // Cache will naturally expire, but we can clear it manually if needed
            $cachePattern = "lesson-resource-pdf-{$resource->lesson_id}-*";
            // Note: Laravel cache doesn't support wildcard deletion
            // Cache will expire naturally after 24 hours
        });
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
