<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\StatisticRequest;
use App\Models\HomepageStatistic;

class StatisticController extends CrudController
{
    protected string $model = HomepageStatistic::class;

    protected string $request = StatisticRequest::class;

    protected array $searchable = ['label', 'value'];

    protected array $filters = ['group' => 'group', 'is_active' => 'is_active'];
}
