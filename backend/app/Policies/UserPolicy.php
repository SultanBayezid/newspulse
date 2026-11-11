<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->role->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->role->isAdmin() || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->role->isAdmin() && $user->id !== $target->id;
    }

    public function ban(User $user, User $target): bool
    {
        return $user->role->isAdmin() && $user->id !== $target->id;
    }
}
