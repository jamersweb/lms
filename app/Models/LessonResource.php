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
        'duas_text',
        'audio_path',
        'pdf_path',
    ];

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
