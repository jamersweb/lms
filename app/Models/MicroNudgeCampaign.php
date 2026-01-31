<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MicroNudgeCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_enabled',
        'schedule_type',
        'send_hour',
        'send_minute',
        'timezone',
        'rotation',
        'audience_filters',
        'clip_ids',
        'last_sent_clip_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'send_hour' => 'integer',
        'send_minute' => 'integer',
        'audience_filters' => 'array',
        'clip_ids' => 'array',
        'last_sent_clip_id' => 'integer',
    ];

    // Schedule types
    public const SCHEDULE_HOURLY = 'hourly';
    public const SCHEDULE_DAILY = 'daily';
    public const SCHEDULE_CRON_LIKE = 'cron_like';

    // Rotation types
    public const ROTATION_RANDOM = 'random';
    public const ROTATION_SEQUENCE = 'sequence';

    /**
     * Get deliveries for this campaign.
     */
    public function deliveries()
    {
        return $this->hasMany(MicroNudgeDelivery::class);
    }

    /**
     * Get the audio clips for this campaign.
     */
    public function getClipsAttribute()
    {
        if ($this->clip_ids && !empty($this->clip_ids)) {
            return AudioClip::whereIn('id', $this->clip_ids)
                ->where('is_active', true)
                ->get();
        }

        // Fallback: all active clips
        return AudioClip::where('is_active', true)->get();
    }

    /**
     * Get the next clip based on rotation strategy.
     */
    public function getNextClip(): ?AudioClip
    {
        $clips = $this->clips;

        if ($clips->isEmpty()) {
            return null;
        }

        if ($this->rotation === self::ROTATION_RANDOM) {
            return $clips->random();
        }

        // Sequence rotation - get clips ordered by their IDs
        $clipsOrdered = $clips->sortBy('id')->values();

        if ($this->last_sent_clip_id) {
            $lastIndex = $clipsOrdered->search(function ($clip) {
                return $clip->id === $this->last_sent_clip_id;
            });

            if ($lastIndex !== false && $lastIndex < $clipsOrdered->count() - 1) {
                return $clipsOrdered->get($lastIndex + 1);
            }
        }

        // Start from beginning
        return $clipsOrdered->first();
    }
}
