<?php

namespace App\Http\Controllers\Community;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\TeamPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Team;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles for the community.
     */
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('viewAny', [Article::class, $current_team]);

        $status = $request->query('status');
        $topicSlug = $request->query('topic');

        $articles = Article::query()
            ->where('team_id', $current_team->id)
            ->with(['author:id,name,email', 'topic:id,name,slug,color'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($topicSlug, function ($query) use ($topicSlug): void {
                $query->whereHas('topic', fn ($q) => $q->where('slug', $topicSlug));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Article $article): array => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'status' => $article->status->value,
                'statusLabel' => $article->status->label(),
                'visibility' => $article->visibility->value,
                'visibilityLabel' => $article->visibility->label(),
                'coverImageUrl' => $article->coverImageUrl(),
                'publishedAt' => $article->published_at?->toIso8601String(),
                'createdAt' => $article->created_at?->toIso8601String(),
                'author' => [
                    'id' => $article->author->id,
                    'name' => $article->author->name,
                ],
                'topic' => $article->topic ? [
                    'id' => $article->topic->id,
                    'name' => $article->topic->name,
                    'slug' => $article->topic->slug,
                    'color' => $article->topic->color,
                ] : null,
                'can' => [
                    'update' => $request->user()?->can('update', $article) ?? false,
                    'publish' => $request->user()?->can('publish', $article) ?? false,
                    'delete' => $request->user()?->can('delete', $article) ?? false,
                ],
            ]);

        $availableTopics = Topic::availableFor($current_team)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color']);

        return Inertia::render('articles/index', [
            'articles' => $articles,
            'topics' => $availableTopics,
            'filters' => [
                'status' => $status,
                'topic' => $topicSlug,
            ],
            'can' => [
                'create' => $request->user()?->can('create', [Article::class, $current_team]) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(Request $request, Team $current_team): Response
    {
        Gate::authorize('create', [Article::class, $current_team]);

        $availableTopics = Topic::availableFor($current_team)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color']);

        return Inertia::render('articles/create', [
            'topics' => $availableTopics,
            'statuses' => collect(ArticleStatus::cases())->map(fn (ArticleStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
            'visibilities' => collect(ArticleVisibility::cases())->map(fn (ArticleVisibility $visibility): array => [
                'value' => $visibility->value,
                'label' => $visibility->label(),
            ])->all(),
            'canPublish' => $request->user()?->hasTeamPermission($current_team, TeamPermission::PublishArticle) ?? false,
        ]);
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(StoreArticleRequest $request, Team $current_team): RedirectResponse
    {
        $validated = $request->validated();

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('articles', config('filesystems.default', 's3'));
        }

        $publishedAt = null;
        if ($validated['status'] === ArticleStatus::Published->value) {
            $publishedAt = now();
        }

        Article::create([
            'team_id' => $current_team->id,
            'user_id' => $request->user()->id,
            'topic_id' => $validated['topic_id'] ?? null,
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'cover_image_path' => $coverImagePath,
            'status' => $validated['status'],
            'visibility' => $validated['visibility'],
            'published_at' => $publishedAt,
        ]);

        return redirect()->route('community.articles.index', $current_team)
            ->with('status', 'Artículo creado exitosamente.');
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(Request $request, Team $current_team, Article $article): Response
    {
        Gate::authorize('update', $article);

        $availableTopics = Topic::availableFor($current_team)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color']);

        return Inertia::render('articles/edit', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'topicId' => $article->topic_id,
                'status' => $article->status->value,
                'visibility' => $article->visibility->value,
                'coverImageUrl' => $article->coverImageUrl(),
            ],
            'topics' => $availableTopics,
            'statuses' => collect(ArticleStatus::cases())->map(fn (ArticleStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->all(),
            'visibilities' => collect(ArticleVisibility::cases())->map(fn (ArticleVisibility $visibility): array => [
                'value' => $visibility->value,
                'label' => $visibility->label(),
            ])->all(),
            'canPublish' => $request->user()?->can('publish', $article) ?? false,
        ]);
    }

    /**
     * Update the specified article in storage.
     */
    public function update(UpdateArticleRequest $request, Team $current_team, Article $article): RedirectResponse
    {
        Gate::authorize('update', $article);

        $validated = $request->validated();

        $dataToUpdate = [
            'title' => $validated['title'],
            'topic_id' => $validated['topic_id'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'status' => $validated['status'],
            'visibility' => $validated['visibility'],
        ];

        if ($request->hasFile('cover_image')) {
            if ($article->cover_image_path) {
                Storage::disk(config('filesystems.default', 's3'))->delete($article->cover_image_path);
            }
            $dataToUpdate['cover_image_path'] = $request->file('cover_image')->store('articles', config('filesystems.default', 's3'));
        }

        if ($validated['status'] === ArticleStatus::Published->value && ! $article->published_at) {
            $dataToUpdate['published_at'] = now();
        }

        $article->update($dataToUpdate);

        return redirect()->route('community.articles.index', $current_team)
            ->with('status', 'Artículo actualizado exitosamente.');
    }

    /**
     * Remove the specified article from storage.
     */
    public function destroy(Request $request, Team $current_team, Article $article): RedirectResponse
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return redirect()->route('community.articles.index', $current_team)
            ->with('status', 'Artículo eliminado.');
    }
}
