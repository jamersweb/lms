<?php

namespace App\Policies;

use App\Models\Habit;
use App\Models\User;

class HabitPolicy
{
    public function view(User $user, Habit $habit): bool
    {
        if ($habit->user_id !== null) {
            return $user->id === $habit->user_id;
        }
        // Lesson-based: user must have completed the lesson
        if (!$habit->lesson_id) {
            return false;
        }
        return \App\Models\LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $habit->lesson_id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    public function create(User $user): bool
    {
        return false; // Only admin can create habits
    }

    public function update(User $user, Habit $habit): bool
    {
        return $habit->user_id !== null && $user->id === $habit->user_id;
    }

    public function delete(User $user, Habit $habit): bool
    {
        return $habit->user_id !== null && $user->id === $habit->user_id;
    }
}
