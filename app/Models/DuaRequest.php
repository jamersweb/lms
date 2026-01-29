<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuaRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_anonymous',
        'request_text',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prayers()
    {
        return $this->hasMany(DuaPrayer::class);
    }
}

