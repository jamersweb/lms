<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id', 'title', 'slug', 'video_provider',
        'youtube_video_id', 'video_path', 'external_video_url',
        'duration_seconds', 'video_duration_seconds', 'is_free_preview',
        'allowed_gender', 'requires_bayah', 'min_level', 'sort_order',
        'requires_reflection', 'reflection_requires_approval',
        'release_at', 'release_day_offset'
    ];

    protected $casts = [
        'release_at' => 'datetime',
        'release_day_offset' => 'integer',
    ];

    /**
     * Get the video URL based on provider
     */
    public function getVideoUrlAttribute(): ?string
    {
        return match($this->video_provider) {
            'youtube' => $this->youtube_video_id
                ? "https://www.youtube-nocookie.com/embed/{$this->youtube_video_id}"
                : null,
            'mp4' => $this->video_path
                ? Storage::url($this->video_path)
                : null,
            'vimeo' => $this->external_video_url
                ? (str_contains($this->external_video_url, 'vimeo.com')
                    ? $this->external_video_url
                    : "https://player.vimeo.com/video/{$this->external_video_url}")
                : null,
            'external' => $this->external_video_url,
            default => null,
        };
    }

    /**
     * Concatenated transcript text for display/search.
     */
    public function getTranscriptTextAttribute(): string
    {
        $segments = $this->relationLoaded('transcriptSegments')
            ? $this->transcriptSegments
            : $this->transcriptSegments()->get();

        return $segments
            ->pluck('text')
            ->filter()
            ->implode(" \n\n");
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class);
    }

    public function transcriptSegments()
    {
        return $this->hasMany(LessonTranscriptSegment::class)->orderBy('start_seconds');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function reflections()
    {
        return $this->hasMany(LessonReflection::class);
    }

    /**
     * Get the reflection for a specific user.
     */
    public function reflectionFor(User $user): ?LessonReflection
    {
        return $this->reflections()->where('user_id', $user->id)->first();
    }

    /**
     * Get the content rule for this lesson.
     */
    public function contentRule(): MorphOne
    {
        return $this->morphOne(ContentRule::class, 'ruleable');
    }

    /**
     * Get the task for this lesson.
     */
    public function task(): MorphOne
    {
        return $this->morphOne(Task::class, 'taskable');
    }
}
