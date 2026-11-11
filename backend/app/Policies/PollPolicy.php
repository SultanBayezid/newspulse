<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function create(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function update(User $user, Poll $poll): bool
    {
        return $user->role->isEditorial();
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $user->role->isEditorial();
    }
}
