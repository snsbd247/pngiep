<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\PartnerRequest;
use App\Models\Partner;

class PartnerController extends CrudController
{
    protected string $model = Partner::class;

    protected string $request = PartnerRequest::class;

    protected array $searchable = ['name', 'category'];

    protected array $filters = ['status' => 'status', 'category' => 'category', 'is_featured' => 'is_featured'];
}
