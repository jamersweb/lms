<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id', 'title', 'slug', 'video_provider', 
        'youtube_video_id', 'video_path', 'external_video_url',
        'duration_seconds', 'is_free_preview', 'sort_order'
    ];

    /**
     * Get the video URL based on provider
     */
    public function getVideoUrlAttribute(): ?string
    {
        return match($this->video_provider) {
            'youtube' => $this->youtube_video_id ? "https://www.youtube.com/embed/{$this->youtube_video_id}" : null,
            'external' => $this->external_video_url,
            'local' => $this->video_path,
            default => null,
        };
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
}
