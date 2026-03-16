<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTriggerEvent extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_trigger_events';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'event_key',
        'template_name',
        'user_id',
        'user_name',
        'phone',
        'message',
        'language',
        'context',
        'status',
        'attempts',
        'claimed_at',
        'claimed_by',
        'processed_at',
        'external_id',
        'error_message',
        'idempotency_key',
    ];

    protected $casts = [
        'context' => 'array',
        'attempts' => 'integer',
        'claimed_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
