<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MicroNudgeDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'audio_clip_id',
        'user_id',
        'channel',
        'sent_at',
        'status',
        'provider_message_id',
        'error',
        'dedupe_key',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public function campaign()
    {
        return $this->belongsTo(MicroNudgeCampaign::class);
    }

    public function audioClip()
    {
        return $this->belongsTo(AudioClip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
