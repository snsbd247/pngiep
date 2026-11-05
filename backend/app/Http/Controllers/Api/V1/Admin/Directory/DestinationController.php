<?php

namespace App\Http\Controllers\Api\V1\Admin\Directory;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Directory\DestinationRequest;
use App\Models\Destination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DestinationController extends CrudController
{
    protected string $model = Destination::class;

    protected string $request = DestinationRequest::class;

    protected array $searchable = ['name', 'slug'];

    protected array $filters = ['status' => 'status', 'is_featured' => 'is_featured', 'destination_category_id' => 'destination_category_id'];

    protected bool $hasSeo = true;

    protected bool $hasSections = true;

    protected array $htmlFields = ['overview'];

    protected function query(): Builder
    {
        return Destination::query()->withCount('universities');
    }

    protected function beforeDelete(Model $model): void
    {
        $count = $model->universities()->count();
        abort_if($count > 0, 422, "Move or delete this destination's {$count} universities first.");
    }
}
