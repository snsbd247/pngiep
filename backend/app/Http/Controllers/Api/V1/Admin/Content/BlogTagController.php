<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\BlogTagRequest;
use App\Models\BlogTag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BlogTagController extends CrudController
{
    protected string $model = BlogTag::class;

    protected string $request = BlogTagRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = [];

    protected bool $sortable = false;

    protected function query(): Builder
    {
        return BlogTag::query()->withCount('blogs');
    }

    /** Detaching keeps the posts; only the tag goes. */
    protected function beforeDelete(Model $model): void
    {
        $model->blogs()->detach();
    }
}
