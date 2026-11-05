<?php

namespace App\Http\Controllers\Api\V1\Admin\Directory;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Directory\UniversityRequest;
use App\Models\University;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UniversityController extends CrudController
{
    protected string $model = University::class;

    protected string $request = UniversityRequest::class;

    protected array $searchable = ['name', 'city'];

    protected array $filters = ['status' => 'status', 'destination_id' => 'destination_id', 'type' => 'type', 'is_featured' => 'is_featured'];

    protected array $with = ['destination:id,name,country_code'];

    protected bool $hasSeo = true;

    protected bool $hasSections = true;

    protected array $htmlFields = ['about'];

    protected function query(): Builder
    {
        return University::query()->withCount('courses');
    }

    protected function prepare(array $data, ?Model $model): array
    {
        if (array_key_exists('video_url', $data)) {
            $data['youtube_id'] = YouTube::id($data['video_url']);
        }

        return parent::prepare($data, $model);
    }

    protected function beforeDelete(Model $model): void
    {
        $count = $model->courses()->count();
        abort_if($count > 0, 422, "Move or delete this university's {$count} courses first.");
    }
}
