<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NudgeDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'micro_habit_nudge_id',
        'sent_at',
        'delivery_status',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function nudge()
    {
        return $this->belongsTo(MicroHabitNudge::class, 'micro_habit_nudge_id');
    }
}
