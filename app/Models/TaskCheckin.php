<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCheckin extends Model
{
    use HasFactory;

    // Set table name explicitly (Laravel would pluralize to task_checkins which is correct, but be explicit)
    protected $table = 'task_checkins';

    protected $fillable = [
        'task_progress_id',
        'checkin_on',
    ];

    protected $casts = [
        'checkin_on' => 'date',
    ];

    /**
     * Get the task progress this check-in belongs to.
     */
    public function taskProgress(): BelongsTo
    {
        return $this->belongsTo(TaskProgress::class);
    }
}
