<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class QuestionMessage extends Model
{
    use HasFactory;

    const SENDER_ROLE_STUDENT = 'student';
    const SENDER_ROLE_TEACHER = 'teacher';
    const SENDER_ROLE_ADMIN = 'admin';

    const AUDIO_TYPE_UPLOAD = 'upload';
    const AUDIO_TYPE_URL = 'url';

    protected $fillable = [
        'question_id',
        'sender_id',
        'sender_role',
        'message',
        'is_internal',
        'audio_type',
        'audio_path',
        'audio_url',
        'read_at',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAudioPlayableUrlAttribute(): ?string
    {
        if ($this->audio_type === self::AUDIO_TYPE_UPLOAD && $this->audio_path) {
            return Storage::url($this->audio_path);
        }

        if ($this->audio_type === self::AUDIO_TYPE_URL && $this->audio_url) {
            return $this->audio_url;
        }

        return null;
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }
}
