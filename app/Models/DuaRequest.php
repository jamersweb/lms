<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuaRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'is_anonymous',
        'content',
        'status',
        'hidden_by',
        'hidden_at',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'hidden_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hiddenBy()
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function prayers()
    {
        return $this->hasMany(DuaPrayer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHidden($query)
    {
        return $query->where('status', 'hidden');
    }
}

