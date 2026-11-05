<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use Illuminate\Database\Eloquent\Builder;
use App\Enums\ContentStatus;
use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\BlogRequest;
use App\Models\Blog;
use App\Models\BlogTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BlogController extends CrudController
{
    protected string $model = Blog::class;

    protected string $request = BlogRequest::class;

    protected array $searchable = ['title', 'excerpt'];

    protected array $filters = ['status' => 'status', 'type' => 'type', 'blog_category_id' => 'blog_category_id', 'is_featured' => 'is_featured'];

    protected array $with = ['category:id,name', 'tags:id,name'];

    /** ?state=draft|scheduled|published (scheduled = published with a future date). */
    protected function query(): Builder
    {
        return Blog::query()->when(request()->query('state'), fn ($q, $state) => match ($state) {
            'draft' => $q->where('status', 'draft'),
            'scheduled' => $q->where('status', 'published')->where('published_at', '>', now()),
            'published' => $q->where('status', 'published')->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now())),
            default => $q,
        });
    }

    protected bool $sortable = false;

    protected bool $hasSeo = true;

    protected array $htmlFields = ['content'];

    protected function applyOrder($query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    protected function prepare(array $data, ?Model $model): array
    {
        unset($data['tags']);

        if (! $model) {
            $data['author_id'] = auth()->id();
            $data['author_name'] ??= auth()->user()?->name;
        }
        // Publishing without a date publishes now; a future date schedules the post.
        if (($data['status'] ?? null) === ContentStatus::Published->value && empty($data['published_at']) && ! $model?->published_at) {
            $data['published_at'] = now();
        }

        return parent::prepare($data, $model);
    }

    protected function afterSave(Model $model, FormRequest $request): void
    {
        parent::afterSave($model, $request);

        if ($request->has('tags')) {
            $ids = collect($request->validated('tags') ?? [])
                ->map(fn ($name) => trim($name))->filter()->unique(fn ($name) => Str::slug($name))
                ->map(fn ($name) => BlogTag::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id);
            $model->tags()->sync($ids);
        }
    }

    protected function transform(Model $model, bool $detail = false): array
    {
        $data = parent::transform($model, $detail);
        $data['tags'] = $model->relationLoaded('tags') ? $model->tags->pluck('name')->all() : $model->tags()->pluck('name')->all();

        return $data;
    }
}
