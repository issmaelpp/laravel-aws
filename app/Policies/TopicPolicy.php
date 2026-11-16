<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    /**
     * Determine whether the user can view any topics.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the topic.
     */
    public function view(?User $user, Topic $topic): bool
    {
        if ($topic->team_id === null) {
            return true;
        }

        return $user !== null && $topic->team !== null && $user->belongsToTeam($topic->team);
    }

    /**
     * Determine whether the user can create topics for a team.
     */
    public function create(User $user, ?Team $team = null): bool
    {
        if ($team !== null) {
            return $user->hasTeamPermission($team, TeamPermission::ManageTopics);
        }

        return false;
    }

    /**
     * Determine whether the user can update the topic.
     */
    public function update(User $user, Topic $topic): bool
    {
        if ($topic->team !== null) {
            return $user->hasTeamPermission($topic->team, TeamPermission::ManageTopics);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the topic.
     */
    public function delete(User $user, Topic $topic): bool
    {
        if ($topic->team !== null) {
            return $user->hasTeamPermission($topic->team, TeamPermission::ManageTopics);
        }

        return false;
    }
}
