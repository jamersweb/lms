<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'user_id', 'event_type', 
        'source_type', 'source_id', 
        'points', 'meta', 'description'
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
