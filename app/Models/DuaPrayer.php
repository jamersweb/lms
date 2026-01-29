<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuaPrayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'dua_request_id',
        'user_id',
    ];

    public function request()
    {
        return $this->belongsTo(DuaRequest::class, 'dua_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
