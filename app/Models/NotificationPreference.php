<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'whatsapp_enabled',
        'quiet_hours_json',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'quiet_hours_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
