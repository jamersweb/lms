<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'instructions',
        'type',
        'required_days',
        'unlock_next_lesson',
        'taskable_type',
        'taskable_id',
    ];

    protected $casts = [
        'required_days' => 'integer',
        'unlock_next_lesson' => 'boolean',
    ];

    /**
     * Get the parent taskable model (lesson or module).
     */
    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all progress records for this task.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(TaskProgress::class);
    }

    /**
     * Get progress for a specific user.
     */
    public function progressFor(User $user): ?TaskProgress
    {
        return $this->progress()->where('user_id', $user->id)->first();
    }
}
