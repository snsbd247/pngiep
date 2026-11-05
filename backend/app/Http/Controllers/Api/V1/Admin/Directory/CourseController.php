<?php

namespace App\Http\Controllers\Api\V1\Admin\Directory;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Directory\CourseRequest;
use App\Models\Course;

class CourseController extends CrudController
{
    protected string $model = Course::class;

    protected string $request = CourseRequest::class;

    protected array $searchable = ['name', 'subject', 'degree_type'];

    protected array $filters = [
        'status' => 'status',
        'university_id' => 'university_id',
        'destination_id' => 'destination_id',
        'study_level' => 'study_level',
        'degree_type' => 'degree_type',
        'is_featured' => 'is_featured',
    ];

    protected array $with = ['university:id,name', 'destination:id,name,country_code'];

    protected bool $hasSeo = true;

    protected array $htmlFields = ['entry_requirements', 'description'];
}
