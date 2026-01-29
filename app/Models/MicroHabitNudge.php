<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MicroHabitNudge extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'audio_path',
        'duration_seconds',
        'sunnah_topic',
        'send_at',
        'target_days',
        'is_active',
    ];

    protected $casts = [
        'send_at' => 'datetime:H:i:s',
        'target_days' => 'array',
        'is_active' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    public function deliveries()
    {
        return $this->hasMany(NudgeDelivery::class);
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? Storage::url($this->audio_path) : null;
    }
}
