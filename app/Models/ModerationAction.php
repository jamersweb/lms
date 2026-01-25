<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModerationAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderator_id', 'target_type', 'target_id', 
        'action', 'reason', 'meta'
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
