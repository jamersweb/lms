<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AudioClip extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'source_type',
        'file_path',
        'external_url',
        'duration_seconds',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    /**
     * Get the playable URL for this clip.
     */
    public function getPlayableUrlAttribute(): string
    {
        if ($this->source_type === 'upload' && $this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }

        if ($this->source_type === 'url' && $this->external_url) {
            return $this->external_url;
        }

        return '';
    }

    /**
     * Get campaigns that use this clip.
     */
    public function campaigns()
    {
        return $this->belongsToMany(MicroNudgeCampaign::class, 'micro_nudge_campaigns', 'id')
            ->whereJsonContains('clip_ids', $this->id);
    }

    /**
     * Get deliveries for this clip.
     */
    public function deliveries()
    {
        return $this->hasMany(MicroNudgeDelivery::class);
    }
}
