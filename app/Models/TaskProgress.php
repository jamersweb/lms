<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskProgress extends Model
{
    use HasFactory;

    // Set table name explicitly (Laravel would pluralize to task_progresses)
    protected $table = 'task_progress';

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'task_id',
        'user_id',
        'status',
        'days_done',
        'last_checkin_on',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'days_done' => 'integer',
        'last_checkin_on' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the task this progress belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user this progress belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all check-ins for this progress.
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(TaskCheckin::class);
    }

    /**
     * Check if user has checked in today.
     */
    public function hasCheckedInToday(): bool
    {
        if (!$this->last_checkin_on) {
            return false;
        }

        return $this->last_checkin_on->isToday();
    }

    /**
     * Get valid status values.
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
        ];
    }
}
