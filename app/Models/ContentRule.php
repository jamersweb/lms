<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_level',
        'requires_bayah',
        'gender',
    ];

    protected $casts = [
        'requires_bayah' => 'boolean',
    ];

    /**
     * Get the parent ruleable model (Course, Module, or Lesson).
     */
    public function ruleable(): MorphTo
    {
        return $this->morphTo();
    }
}
