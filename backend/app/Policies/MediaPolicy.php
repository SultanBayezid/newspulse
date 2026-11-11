<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function update(User $user, Media $media): bool
    {
        return $user->role->isEditorial() || $media->user_id === $user->id;
    }

    public function delete(User $user, Media $media): bool
    {
        return $user->role->isEditorial() || $media->user_id === $user->id;
    }
}
