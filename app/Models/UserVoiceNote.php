<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserVoiceNote extends Model
{
    use HasFactory;

    const AUDIO_TYPE_UPLOAD = 'upload';
    const AUDIO_TYPE_URL = 'url';

    protected $fillable = [
        'user_id',
        'created_by',
        'title',
        'note',
        'audio_type',
        'audio_path',
        'audio_url',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAudioUrlAttribute(): ?string
    {
        if ($this->audio_type === self::AUDIO_TYPE_UPLOAD && $this->audio_path) {
            return Storage::url($this->audio_path);
        }

        if ($this->audio_type === self::AUDIO_TYPE_URL && $this->audio_url) {
            return $this->audio_url;
        }

        return null;
    }
}
