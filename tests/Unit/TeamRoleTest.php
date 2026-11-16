<?php

use App\Enums\TeamPermission;
use App\Enums\TeamRole;

test('owner has all permissions', function (): void {
    $owner = TeamRole::Owner;

    expect($owner->permissions())->toHaveCount(count(TeamPermission::cases()));
    expect($owner->hasPermission(TeamPermission::CreateArticle))->toBeTrue();
    expect($owner->hasPermission(TeamPermission::PublishArticle))->toBeTrue();
    expect($owner->hasPermission(TeamPermission::DeleteAnyArticle))->toBeTrue();
    expect($owner->hasPermission(TeamPermission::DeleteTeam))->toBeTrue();
});

test('admin can manage team and publish articles', function (): void {
    $admin = TeamRole::Admin;

    expect($admin->hasPermission(TeamPermission::CreateArticle))->toBeTrue();
    expect($admin->hasPermission(TeamPermission::PublishArticle))->toBeTrue();
    expect($admin->hasPermission(TeamPermission::UpdateAnyArticle))->toBeTrue();
    expect($admin->hasPermission(TeamPermission::DeleteAnyArticle))->toBeTrue();
    expect($admin->hasPermission(TeamPermission::ManageTopics))->toBeTrue();
    expect($admin->hasPermission(TeamPermission::DeleteTeam))->toBeFalse();
});

test('editor can edit and publish articles but cannot manage team members', function (): void {
    $editor = TeamRole::Editor;

    expect($editor->hasPermission(TeamPermission::CreateArticle))->toBeTrue();
    expect($editor->hasPermission(TeamPermission::PublishArticle))->toBeTrue();
    expect($editor->hasPermission(TeamPermission::UpdateAnyArticle))->toBeTrue();
    expect($editor->hasPermission(TeamPermission::AddMember))->toBeFalse();
    expect($editor->hasPermission(TeamPermission::DeleteTeam))->toBeFalse();
});

test('author can only create, edit and delete their own articles', function (): void {
    $author = TeamRole::Author;

    expect($author->hasPermission(TeamPermission::CreateArticle))->toBeTrue();
    expect($author->hasPermission(TeamPermission::UpdateOwnArticle))->toBeTrue();
    expect($author->hasPermission(TeamPermission::DeleteOwnArticle))->toBeTrue();
    expect($author->hasPermission(TeamPermission::PublishArticle))->toBeFalse();
    expect($author->hasPermission(TeamPermission::UpdateAnyArticle))->toBeFalse();
    expect($author->hasPermission(TeamPermission::DeleteAnyArticle))->toBeFalse();
});

test('member has no editorial permissions by default', function (): void {
    $member = TeamRole::Member;

    expect($member->permissions())->toBeEmpty();
    expect($member->hasPermission(TeamPermission::CreateArticle))->toBeFalse();
    expect($member->hasPermission(TeamPermission::PublishArticle))->toBeFalse();
});

test('hierarchy level respects privileges', function (): void {
    expect(TeamRole::Owner->isAtLeast(TeamRole::Admin))->toBeTrue();
    expect(TeamRole::Admin->isAtLeast(TeamRole::Editor))->toBeTrue();
    expect(TeamRole::Editor->isAtLeast(TeamRole::Author))->toBeTrue();
    expect(TeamRole::Author->isAtLeast(TeamRole::Member))->toBeTrue();
    expect(TeamRole::Member->isAtLeast(TeamRole::Author))->toBeFalse();
});

test('assignable roles do not include owner', function (): void {
    $assignable = TeamRole::assignable();
    $values = array_column($assignable, 'value');

    expect($values)->toContain('admin', 'editor', 'author', 'member');
    expect($values)->not->toContain('owner');
});
