<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Enums\ContentStatus;
use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\NewsRequest;
use App\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsController extends CrudController
{
    protected string $model = News::class;

    protected string $request = NewsRequest::class;

    protected array $searchable = ['title', 'excerpt'];

    protected array $filters = ['status' => 'status', 'is_featured' => 'is_featured'];

    protected bool $sortable = false;

    protected bool $hasSeo = true;

    protected array $htmlFields = ['content'];

    /** ?state=draft|scheduled|published (scheduled = published with a future date). */
    protected function query(): Builder
    {
        return News::query()->when(request()->query('state'), fn ($q, $state) => match ($state) {
            'draft' => $q->where('status', 'draft'),
            'scheduled' => $q->where('status', 'published')->where('published_at', '>', now()),
            'published' => $q->where('status', 'published')->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now())),
            default => $q,
        });
    }

    protected function applyOrder($query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    protected function prepare(array $data, ?Model $model): array
    {
        if (! $model) {
            $data['author_id'] = auth()->id();
            $data['author_name'] ??= auth()->user()?->name;
        }
        // Publishing without a date publishes now; a future date schedules the article.
        if (($data['status'] ?? null) === ContentStatus::Published->value && empty($data['published_at']) && ! $model?->published_at) {
            $data['published_at'] = now();
        }

        return parent::prepare($data, $model);
    }
}
