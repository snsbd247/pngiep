<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\HeroSlideRequest;
use App\Models\HeroSlide;

class HeroSlideController extends CrudController
{
    protected string $model = HeroSlide::class;

    protected string $request = HeroSlideRequest::class;

    protected array $searchable = ['title', 'badge'];

    protected array $filters = ['is_active' => 'is_active'];
}
