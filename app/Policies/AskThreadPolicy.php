<?php

namespace App\Policies;

use App\Models\AskThread;
use App\Models\User;

class AskThreadPolicy
{
    public function view(User $user, AskThread $thread): bool
    {
        return $user->is_admin || $thread->user_id === $user->id;
    }

    public function reply(User $user, AskThread $thread): bool
    {
        if ($thread->status !== 'open') {
            return false;
        }

        return $user->is_admin || $thread->user_id === $user->id;
    }
}

