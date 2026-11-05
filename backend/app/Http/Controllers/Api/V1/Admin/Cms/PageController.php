<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\PageRequest;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;

class PageController extends CrudController
{
    protected string $model = Page::class;

    protected string $request = PageRequest::class;

    protected array $searchable = ['title', 'slug'];

    protected array $filters = ['status' => 'status', 'template' => 'template', 'page_category_id' => 'page_category_id'];

    protected bool $sortable = false;

    protected bool $hasSeo = true;

    protected bool $hasSections = true;

    protected array $htmlFields = ['content'];

    protected function beforeDelete(Model $model): void
    {
        abort_if($model->template === 'listing', 422, 'Built-in list pages cannot be deleted. Unpublish sections instead.');
    }

    protected function prepare(array $data, ?Model $model): array
    {
        if (($data['status'] ?? null) === 'published' && ! $model?->published_at) {
            $data['published_at'] = now();
        }
        if (! $model) {
            $data['created_by'] = auth()->id();
        }

        return parent::prepare($data, $model);
    }
}
