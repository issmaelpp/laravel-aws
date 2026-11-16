<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Article;
use App\Models\Team;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Determine whether the user can view a list of articles.
     */
    public function viewAny(?User $user, ?Team $team = null): bool
    {
        if ($team === null) {
            return true;
        }

        return $user !== null && $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the article.
     */
    public function view(?User $user, Article $article): bool
    {
        if ($article->isPublic()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($article->isPublished() && $user->belongsToTeam($article->team)) {
            return true;
        }

        if ($user->id === $article->user_id) {
            return $user->belongsToTeam($article->team);
        }

        return $user->hasTeamPermission($article->team, TeamPermission::UpdateAnyArticle);
    }

    /**
     * Determine whether the user can create articles for the team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->hasTeamPermission($team, TeamPermission::CreateArticle);
    }

    /**
     * Determine whether the user can update the article.
     */
    public function update(User $user, Article $article): bool
    {
        if ($user->id === $article->user_id && $user->hasTeamPermission($article->team, TeamPermission::UpdateOwnArticle)) {
            return true;
        }

        return $user->hasTeamPermission($article->team, TeamPermission::UpdateAnyArticle);
    }

    /**
     * Determine whether the user can publish the article.
     */
    public function publish(User $user, Article $article): bool
    {
        return $user->hasTeamPermission($article->team, TeamPermission::PublishArticle);
    }

    /**
     * Determine whether the user can delete the article.
     */
    public function delete(User $user, Article $article): bool
    {
        if ($user->id === $article->user_id && $user->hasTeamPermission($article->team, TeamPermission::DeleteOwnArticle)) {
            return true;
        }

        return $user->hasTeamPermission($article->team, TeamPermission::DeleteAnyArticle);
    }
}
