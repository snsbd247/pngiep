<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\TestimonialRequest;
use App\Models\Testimonial;

class TestimonialController extends CrudController
{
    protected string $model = Testimonial::class;

    protected string $request = TestimonialRequest::class;

    protected array $searchable = ['name', 'university', 'course', 'content'];

    protected array $filters = ['status' => 'status', 'is_featured' => 'is_featured'];
}
