<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\GalleryVideoRequest;
use App\Models\GalleryVideo;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Model;

class GalleryVideoController extends CrudController
{
    protected string $model = GalleryVideo::class;

    protected string $request = GalleryVideoRequest::class;

    protected array $searchable = ['title', 'description'];

    protected array $filters = ['status' => 'status', 'gallery_category_id' => 'gallery_category_id', 'is_featured' => 'is_featured'];

    protected array $with = ['category:id,name'];

    /** Only the YouTube URL is stored; the video ID is extracted server-side (no uploads). */
    protected function prepare(array $data, ?Model $model): array
    {
        $data['youtube_id'] = YouTube::id($data['youtube_url']);

        return parent::prepare($data, $model);
    }
}
