<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function manage(User $user): bool
    {
        return $user->role->isEditorial();
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->manage($user);
    }
}
