<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function moderate(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function viewAny(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->role->isEditorial() || $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->role->isEditorial() || $comment->user_id === $user->id;
    }
}
