<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\TimelineItemRequest;
use App\Models\TimelineItem;

class TimelineItemController extends CrudController
{
    protected string $model = TimelineItem::class;

    protected string $request = TimelineItemRequest::class;

    protected array $searchable = ['title', 'year'];

    protected array $filters = ['is_active' => 'is_active'];
}
