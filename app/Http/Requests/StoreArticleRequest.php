<?php

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\TeamPermission;
use App\Models\Article;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('current_team');

        if (! $team instanceof Team) {
            $team = $this->user()?->currentTeam;
        }

        if (! $team) {
            return false;
        }

        if (! $this->user()->can('create', [Article::class, $team])) {
            return false;
        }

        if ($this->input('status') === ArticleStatus::Published->value && ! $this->user()->hasTeamPermission($team, TeamPermission::PublishArticle)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'topic_id' => ['nullable', 'exists:topics,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'visibility' => ['required', Rule::enum(ArticleVisibility::class)],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'cover_image' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
