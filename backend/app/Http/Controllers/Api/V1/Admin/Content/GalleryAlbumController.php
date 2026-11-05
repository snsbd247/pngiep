<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\GalleryAlbumRequest;
use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class GalleryAlbumController extends CrudController
{
    protected string $model = GalleryAlbum::class;

    protected string $request = GalleryAlbumRequest::class;

    protected array $searchable = ['title', 'description'];

    protected array $filters = ['status' => 'status', 'gallery_category_id' => 'gallery_category_id', 'is_featured' => 'is_featured'];

    protected array $with = ['category:id,name'];

    protected function query(): Builder
    {
        return GalleryAlbum::query()->withCount('images');
    }

    protected function prepare(array $data, ?Model $model): array
    {
        unset($data['photos']);

        return parent::prepare($data, $model);
    }

    /** Photos are edited as an ordered list of image paths and stored as gallery_images rows. */
    protected function afterSave(Model $model, FormRequest $request): void
    {
        parent::afterSave($model, $request);

        if (! $request->has('photos')) {
            return;
        }

        /** @var GalleryAlbum $model */
        $paths = array_values($request->validated('photos') ?? []);
        $existing = $model->images()->get()->keyBy('image');

        foreach ($paths as $position => $path) {
            $attributes = ['sort_order' => $position, 'gallery_category_id' => $model->gallery_category_id, 'status' => $model->status];
            ($image = $existing->get($path)) ? $image->update($attributes) : $model->images()->create($attributes + ['image' => $path, 'title' => $model->title]);
        }

        $model->images()->whereNotIn('image', $paths)->delete();
    }

    protected function transform(Model $model, bool $detail = false): array
    {
        $data = parent::transform($model, $detail);
        if ($detail) {
            $data['photos'] = $model->images()->pluck('image')->all();
        }

        return $data;
    }
}
