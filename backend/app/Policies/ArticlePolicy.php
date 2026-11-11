<?php

namespace App\Policies;

use App\Enums\ArticleStatus;
use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function view(User $user, Article $article): bool
    {
        return $user->role->isEditorial()
            || $article->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role->isStaff();
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->role->isEditorial()) {
            return true;
        }

        return $article->user_id === $user->id
            && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Review], true);
    }

    public function delete(User $user, Article $article): bool
    {
        if ($user->role->isEditorial()) {
            return true;
        }

        return $article->user_id === $user->id
            && $article->status !== ArticleStatus::Published;
    }

    public function publish(User $user): bool
    {
        return $user->role->isEditorial();
    }
}
