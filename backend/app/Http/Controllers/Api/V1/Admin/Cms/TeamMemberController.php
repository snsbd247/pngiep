<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\TeamMemberRequest;
use App\Models\TeamMember;

class TeamMemberController extends CrudController
{
    protected string $model = TeamMember::class;

    protected string $request = TeamMemberRequest::class;

    protected array $searchable = ['name', 'designation'];

    protected array $filters = ['is_active' => 'is_active'];
}
