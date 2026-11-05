<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\FaqRequest;
use App\Models\Faq;

class FaqController extends CrudController
{
    protected string $model = Faq::class;

    protected string $request = FaqRequest::class;

    protected array $searchable = ['question', 'category'];

    protected array $filters = ['status' => 'status', 'page_key' => 'page_key', 'category' => 'category'];

    protected array $htmlFields = ['answer'];
}
