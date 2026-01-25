<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\HabitLog;
use App\Models\User;

class HabitLogPolicy
{
    public function view(User $user, HabitLog $habitLog): bool
    {
        return $user->id === $habitLog->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, HabitLog $habitLog): bool
    {
        return $user->id === $habitLog->user_id;
    }

    public function delete(User $user, HabitLog $habitLog): bool
    {
        return $user->id === $habitLog->user_id;
    }
}
