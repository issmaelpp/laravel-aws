<?php

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\TeamRole;
use App\Models\Article;
use App\Models\Team;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('team members can view articles listing', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $article = Article::factory()->create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'title' => 'Artículo de la Comunidad',
    ]);

    $response = $this->actingAs($user)
        ->get(route('community.articles.index', $team));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('articles/index')
        ->has('articles.data', 1)
        ->where('articles.data.0.title', 'Artículo de la Comunidad')
    );
});

test('non team members cannot view community articles', function (): void {
    $team = Team::factory()->create();
    $stranger = User::factory()->create();

    $response = $this->actingAs($stranger)
        ->get(route('community.articles.index', $team));

    $response->assertForbidden();
});

test('author can create a draft article with cover image', function (): void {
    Storage::fake(config('filesystems.default', 's3'));

    $team = Team::factory()->create();
    $author = User::factory()->create();
    $team->members()->attach($author, ['role' => TeamRole::Author->value]);

    $topic = Topic::factory()->create();

    $file = UploadedFile::fake()->image('portada.jpg');

    $response = $this->actingAs($author)
        ->post(route('community.articles.store', $team), [
            'title' => 'Mi Primer Borrador Comunitario',
            'topic_id' => $topic->id,
            'excerpt' => 'Breve resumen del contenido.',
            'content' => 'Contenido completo del artículo.',
            'visibility' => ArticleVisibility::Community->value,
            'status' => ArticleStatus::Draft->value,
            'cover_image' => $file,
        ]);

    $response->assertRedirect(route('community.articles.index', $team));

    $article = Article::where('title', 'Mi Primer Borrador Comunitario')->first();
    expect($article)->not->toBeNull();
    expect($article->status)->toBe(ArticleStatus::Draft);
    expect($article->user_id)->toBe($author->id);
    expect($article->cover_image_path)->not->toBeNull();

    Storage::disk(config('filesystems.default', 's3'))->assertExists($article->cover_image_path);
});

test('author cannot publish directly without publish permission', function (): void {
    $team = Team::factory()->create();
    $author = User::factory()->create();
    $team->members()->attach($author, ['role' => TeamRole::Author->value]);

    $response = $this->actingAs($author)
        ->post(route('community.articles.store', $team), [
            'title' => 'Intento de publicación directa',
            'content' => 'Contenido...',
            'visibility' => ArticleVisibility::Public->value,
            'status' => ArticleStatus::Published->value,
        ]);

    $response->assertForbidden();
});

test('editor can publish articles directly', function (): void {
    $team = Team::factory()->create();
    $editor = User::factory()->create();
    $team->members()->attach($editor, ['role' => TeamRole::Editor->value]);

    $response = $this->actingAs($editor)
        ->post(route('community.articles.store', $team), [
            'title' => 'Artículo Publicado por Editor',
            'content' => 'Contenido aprobado.',
            'visibility' => ArticleVisibility::Public->value,
            'status' => ArticleStatus::Published->value,
        ]);

    $response->assertRedirect(route('community.articles.index', $team));

    $article = Article::where('title', 'Artículo Publicado por Editor')->first();
    expect($article)->not->toBeNull();
    expect($article->status)->toBe(ArticleStatus::Published);
    expect($article->published_at)->not->toBeNull();
});

test('author can update own article', function (): void {
    $team = Team::factory()->create();
    $author = User::factory()->create();
    $team->members()->attach($author, ['role' => TeamRole::Author->value]);

    $article = Article::factory()->create([
        'team_id' => $team->id,
        'user_id' => $author->id,
        'title' => 'Título Inicial',
        'status' => ArticleStatus::Draft,
    ]);

    $response = $this->actingAs($author)
        ->put(route('community.articles.update', [$team, $article]), [
            'title' => 'Título Actualizado',
            'content' => 'Contenido actualizado.',
            'visibility' => ArticleVisibility::Community->value,
            'status' => ArticleStatus::Draft->value,
        ]);

    $response->assertRedirect(route('community.articles.index', $team));

    expect($article->fresh()->title)->toBe('Título Actualizado');
});

test('member cannot create or delete articles', function (): void {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $article = Article::factory()->create([
        'team_id' => $team->id,
    ]);

    // Create attempt
    $this->actingAs($member)
        ->post(route('community.articles.store', $team), [
            'title' => 'Intento no autorizado',
            'content' => '...',
            'visibility' => ArticleVisibility::Community->value,
            'status' => ArticleStatus::Draft->value,
        ])
        ->assertForbidden();

    // Delete attempt
    $this->actingAs($member)
        ->delete(route('community.articles.destroy', [$team, $article]))
        ->assertForbidden();
});
