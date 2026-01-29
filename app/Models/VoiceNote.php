<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VoiceNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'ask_thread_id',
        'sender_id',
        'audio_path',
        'duration_seconds',
        'transcription',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
    ];

    public function thread()
    {
        return $this->belongsTo(AskThread::class, 'ask_thread_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAudioUrlAttribute(): ?string
    {
        return $this->audio_path ? Storage::url($this->audio_path) : null;
    }
}
