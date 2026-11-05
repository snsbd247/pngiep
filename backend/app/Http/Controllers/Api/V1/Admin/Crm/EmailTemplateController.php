<?php

namespace App\Http\Controllers\Api\V1\Admin\Crm;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Crm\EmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/** The set of templates is fixed (seeded); admins edit subject, body and on/off. */
class EmailTemplateController extends CrudController
{
    protected string $model = EmailTemplate::class;

    protected string $request = EmailTemplateRequest::class;

    protected array $searchable = ['name', 'subject'];

    protected array $filters = [];

    protected bool $sortable = false;

    protected array $htmlFields = ['body'];

    protected function applyOrder(Builder $query): void
    {
        $query->orderBy('name');
    }

    public function store(): JsonResponse
    {
        abort(405, 'Email templates are fixed. Edit an existing template instead.');
    }

    public function destroy(int $id): JsonResponse
    {
        abort(405, 'Email templates cannot be deleted. Switch the template off instead.');
    }
}
