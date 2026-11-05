<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\ServiceRequest;
use App\Models\Service;

class ServiceController extends CrudController
{
    protected string $model = Service::class;

    protected string $request = ServiceRequest::class;

    protected array $searchable = ['title', 'short_description'];

    protected array $filters = ['status' => 'status', 'is_featured' => 'is_featured'];

    protected bool $hasSeo = true;

    protected bool $hasSections = true;

    protected array $htmlFields = ['description'];
}
