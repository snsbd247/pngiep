<?php

namespace App\Http\Controllers\Api\V1\Admin\Content;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Content\SuccessStoryRequest;
use App\Models\SuccessStory;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SuccessStoryController extends CrudController
{
    protected string $model = SuccessStory::class;

    protected string $request = SuccessStoryRequest::class;

    protected array $searchable = ['student_name', 'university_name', 'course_name'];

    protected array $filters = ['status' => 'status', 'destination_id' => 'destination_id', 'is_featured' => 'is_featured', 'story_category_id' => 'story_category_id'];

    protected array $with = ['destination:id,name,country_code', 'university:id,name'];

    protected bool $hasSeo = true;

    protected array $htmlFields = ['story'];

    /** ?submissions=1 lists stories sent in by visitors (awaiting review). */
    protected function query(): Builder
    {
        return SuccessStory::query()->when(request()->boolean('submissions'), fn ($q) => $q->whereNotNull('submitter_email'));
    }

    protected function prepare(array $data, ?Model $model): array
    {
        if (array_key_exists('video_url', $data)) {
            $data['youtube_id'] = YouTube::id($data['video_url']);
        }

        return parent::prepare($data, $model);
    }
}
