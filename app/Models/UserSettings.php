<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'leaderboard_opt_out', 'display_name'
    ];

    protected $casts = [
        'leaderboard_opt_out' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
