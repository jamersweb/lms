<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'meta',
        'sent_on',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_on' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
