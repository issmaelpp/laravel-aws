<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('generates slug automatically when creating topic', function (): void {
    $topic = Topic::create([
        'name' => 'Ciencia Espacial',
        'description' => 'Noticias sobre astronomía y exploración espacial.',
        'color' => '#123456',
        'is_active' => true,
    ]);

    expect($topic->slug)->toBe('ciencia-espacial');
});

test('topic scopes filter active and global topics correctly', function (): void {
    $globalActive = Topic::factory()->create(['team_id' => null, 'is_active' => true]);
    $globalInactive = Topic::factory()->inactive()->create(['team_id' => null]);

    $team = Team::factory()->create();
    $teamTopic = Topic::factory()->forTeam($team)->create(['is_active' => true]);

    $activeTopics = Topic::active()->pluck('id');
    expect($activeTopics)->toContain($globalActive->id);
    expect($activeTopics)->toContain($teamTopic->id);
    expect($activeTopics)->not->toContain($globalInactive->id);

    $globalTopics = Topic::global()->pluck('id');
    expect($globalTopics)->toContain($globalActive->id);
    expect($globalTopics)->not->toContain($teamTopic->id);

    $availableForTeam = Topic::availableFor($team)->pluck('id');
    expect($availableForTeam)->toContain($globalActive->id);
    expect($availableForTeam)->toContain($teamTopic->id);
});

test('topic policy authorizes team admins to manage team topics', function (): void {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $teamTopic = Topic::factory()->forTeam($team)->create();
    $globalTopic = Topic::factory()->create(['team_id' => null]);

    // Anyone can view global topic
    expect(Gate::forUser(null)->allows('view', $globalTopic))->toBeTrue();

    // Team admin can update team topic
    expect(Gate::forUser($admin)->allows('update', $teamTopic))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $teamTopic))->toBeTrue();

    // Regular member cannot update team topic
    expect(Gate::forUser($member)->allows('update', $teamTopic))->toBeFalse();

    // Team admin cannot update global topic (only global staff would)
    expect(Gate::forUser($admin)->allows('update', $globalTopic))->toBeFalse();
});
