<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\TeamRole;
use App\Models\Article;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('generates unique slugs for articles automatically', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $article1 = Article::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'title' => 'Gran Lanzamiento Comunitario',
        'content' => 'Contenido del primer artículo...',
        'status' => ArticleStatus::Draft,
        'visibility' => ArticleVisibility::Community,
    ]);

    $article2 = Article::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'title' => 'Gran Lanzamiento Comunitario',
        'content' => 'Contenido del segundo artículo...',
        'status' => ArticleStatus::Draft,
        'visibility' => ArticleVisibility::Community,
    ]);

    expect($article1->slug)->toBe('gran-lanzamiento-comunitario');
    expect($article2->slug)->toBe('gran-lanzamiento-comunitario-1');
});

test('article relationships resolve correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $topic = Topic::factory()->create();

    $article = Article::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'topic_id' => $topic->id,
    ]);

    expect($article->team->id)->toBe($team->id);
    expect($article->user->id)->toBe($user->id);
    expect($article->author->id)->toBe($user->id);
    expect($article->topic->id)->toBe($topic->id);
});

test('article scopes filter published and public articles', function (): void {
    $publishedPublic = Article::factory()->published()->public()->create();
    $publishedCommunity = Article::factory()->published()->create([
        'visibility' => ArticleVisibility::Community,
    ]);
    $draft = Article::factory()->create([
        'status' => ArticleStatus::Draft,
        'published_at' => null,
    ]);

    $publishedIds = Article::published()->pluck('id');
    expect($publishedIds)->toContain($publishedPublic->id);
    expect($publishedIds)->toContain($publishedCommunity->id);
    expect($publishedIds)->not->toContain($draft->id);

    $publicIds = Article::public()->pluck('id');
    expect($publicIds)->toContain($publishedPublic->id);
    expect($publicIds)->not->toContain($publishedCommunity->id);
    expect($publicIds)->not->toContain($draft->id);
});

test('article policy permits public access to public published articles', function (): void {
    $article = Article::factory()->published()->public()->create();

    expect(Gate::forUser(null)->allows('view', $article))->toBeTrue();
});

test('article policy restricts draft visibility to author and editors', function (): void {
    $team = Team::factory()->create();
    $author = User::factory()->create();
    $editor = User::factory()->create();
    $member = User::factory()->create();
    $stranger = User::factory()->create();

    $team->members()->attach($author, ['role' => TeamRole::Author->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $draft = Article::factory()->create([
        'team_id' => $team->id,
        'user_id' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    // Author can view own draft
    expect(Gate::forUser($author)->allows('view', $draft))->toBeTrue();

    // Editor can view draft in the team
    expect(Gate::forUser($editor)->allows('view', $draft))->toBeTrue();

    // Member and stranger cannot view draft
    expect(Gate::forUser($member)->allows('view', $draft))->toBeFalse();
    expect(Gate::forUser($stranger)->allows('view', $draft))->toBeFalse();
});

test('article policy controls create, update and publish permissions', function (): void {
    $team = Team::factory()->create();
    $author = User::factory()->create();
    $editor = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($author, ['role' => TeamRole::Author->value]);
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $article = Article::factory()->create([
        'team_id' => $team->id,
        'user_id' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    // Creation
    expect(Gate::forUser($author)->allows('create', [Article::class, $team]))->toBeTrue();
    expect(Gate::forUser($editor)->allows('create', [Article::class, $team]))->toBeTrue();
    expect(Gate::forUser($member)->allows('create', [Article::class, $team]))->toBeFalse();

    // Author can update own article, but cannot publish
    expect(Gate::forUser($author)->allows('update', $article))->toBeTrue();
    expect(Gate::forUser($author)->allows('publish', $article))->toBeFalse();

    // Editor can update any article and publish
    expect(Gate::forUser($editor)->allows('update', $article))->toBeTrue();
    expect(Gate::forUser($editor)->allows('publish', $article))->toBeTrue();

    // Member cannot update or publish
    expect(Gate::forUser($member)->allows('update', $article))->toBeFalse();
    expect(Gate::forUser($member)->allows('publish', $article))->toBeFalse();
});
