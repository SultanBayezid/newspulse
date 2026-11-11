<?php

namespace App\Policies;

use App\Models\User;

class EditorialPolicy
{
    /**
     * Shared policy for categories, tags, pages, polls, newsletter
     * subscribers and the activity log - editorial staff only.
     */
    public function manage(User $user): bool
    {
        return $user->role->isEditorial();
    }
}
