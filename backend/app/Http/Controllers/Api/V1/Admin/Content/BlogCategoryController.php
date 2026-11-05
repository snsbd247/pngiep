<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\BlogCategoryRequest;
use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BlogCategoryController extends CrudController
{
    protected string $model = BlogCategory::class;

    protected string $request = BlogCategoryRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = ['type' => 'type', 'is_active' => 'is_active'];

    protected function query(): Builder
    {
        return BlogCategory::query()->withCount('blogs');
    }

    protected function beforeDelete(Model $model): void
    {
        $count = $model->blogs()->count();
        abort_if($count > 0, 422, "This category still has {$count} posts. Move them to another category first.");
    }
}
