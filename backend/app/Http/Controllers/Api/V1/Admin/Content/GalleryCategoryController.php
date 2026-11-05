<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\GalleryCategoryRequest;
use App\Models\GalleryCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GalleryCategoryController extends CrudController
{
    protected string $model = GalleryCategory::class;

    protected string $request = GalleryCategoryRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = ['type' => 'type', 'is_active' => 'is_active'];

    protected function query(): Builder
    {
        return GalleryCategory::query()->withCount(['images', 'videos', 'albums']);
    }

    protected function beforeDelete(Model $model): void
    {
        $count = $model->images()->count() + $model->videos()->count() + $model->albums()->count();
        abort_if($count > 0, 422, "This category still has {$count} albums, photos or videos. Move them first.");
    }
}
