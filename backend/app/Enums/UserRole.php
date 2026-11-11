<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Editor = 'editor';
    case Author = 'author';
    case Subscriber = 'subscriber';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Editor => 'Editor',
            self::Author => 'Author',
            self::Subscriber => 'Subscriber',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isEditorial(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Editor], true);
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Editor, self::Author], true);
    }

    /**
     * Ability list granted to this role. The super admin bypasses
     * authorization entirely through Gate::before().
     */
    public function abilities(): array
    {
        return match ($this) {
            self::Editor => [
                'dashboard.view',
                'articles.viewAny', 'articles.create', 'articles.updateAny', 'articles.deleteAny', 'articles.publish',
                'categories.manage', 'tags.manage', 'media.manage', 'pages.manage',
                'comments.moderate', 'polls.manage', 'newsletter.manage', 'activity.view',
            ],
            self::Author => [
                'dashboard.view',
                'articles.viewAny', 'articles.create', 'articles.updateOwn', 'articles.deleteOwn',
                'media.manage',
            ],
            default => [],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
