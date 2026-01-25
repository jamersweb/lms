<?php

namespace App\Policies;

use App\Models\DiscussionReply;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DiscussionReplyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DiscussionReply $discussionReply): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DiscussionReply $discussionReply): bool
    {
        if ($user->is_admin) return true;
        return $user->id === $discussionReply->user_id && $discussionReply->discussion->status === 'open';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DiscussionReply $discussionReply): bool
    {
        if ($user->is_admin) return true;
        return $user->id === $discussionReply->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DiscussionReply $discussionReply): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DiscussionReply $discussionReply): bool
    {
        return $user->is_admin;
    }
}
