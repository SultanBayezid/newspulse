<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function create(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function update(User $user, Page $page): bool
    {
        return $user->role->isEditorial();
    }

    public function delete(User $user, Page $page): bool
    {
        return $user->role->isEditorial();
    }
}
