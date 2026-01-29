<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AskMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ask_thread_id',
        'user_id',
        'sender_type',
        'body',
    ];

    public function thread()
    {
        return $this->belongsTo(AskThread::class, 'ask_thread_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
