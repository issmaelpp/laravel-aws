<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Editor = 'editor';
    case Author = 'author';
    case Member = 'member';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<TeamPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => TeamPermission::cases(),
            self::Admin => [
                TeamPermission::UpdateTeam,
                TeamPermission::CreateInvitation,
                TeamPermission::CancelInvitation,
                TeamPermission::CreateArticle,
                TeamPermission::UpdateOwnArticle,
                TeamPermission::UpdateAnyArticle,
                TeamPermission::PublishArticle,
                TeamPermission::DeleteOwnArticle,
                TeamPermission::DeleteAnyArticle,
                TeamPermission::ManageTopics,
            ],
            self::Editor => [
                TeamPermission::CreateArticle,
                TeamPermission::UpdateOwnArticle,
                TeamPermission::UpdateAnyArticle,
                TeamPermission::PublishArticle,
                TeamPermission::DeleteOwnArticle,
            ],
            self::Author => [
                TeamPermission::CreateArticle,
                TeamPermission::UpdateOwnArticle,
                TeamPermission::DeleteOwnArticle,
            ],
            self::Member => [],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Get the hierarchy level for this role.
     * Higher numbers indicate higher privileges.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 5,
            self::Admin => 4,
            self::Editor => 3,
            self::Author => 2,
            self::Member => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(TeamRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to team members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
