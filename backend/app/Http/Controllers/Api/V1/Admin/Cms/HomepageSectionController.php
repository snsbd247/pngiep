<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\HomepageSectionRequest;
use App\Models\HomepageSection;
use App\Support\SectionSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Homepage sections are a fixed set (seeded); admins edit, reorder and toggle them.
 */
class HomepageSectionController extends CrudController
{
    protected string $model = HomepageSection::class;

    protected string $request = HomepageSectionRequest::class;

    protected array $filters = [];

    /** The section set is fixed by the theme; only edit / reorder / toggle are allowed. */
    public function store(): JsonResponse
    {
        abort(405, 'Homepage sections are fixed. Use the visibility toggle instead.');
    }

    public function destroy(int $id): JsonResponse
    {
        abort(405, 'Homepage sections are fixed. Use the visibility toggle instead.');
    }

    protected function prepare(array $data, ?Model $model): array
    {
        if (array_key_exists('data', $data)) {
            $schema = SectionSchema::homepageSection($model->key) ?? ['fields' => []];
            $data['data'] = SectionSchema::clean($schema['fields'], $data['data']);
        }

        return parent::prepare($data, $model);
    }

    protected function transform(Model $model, bool $detail = false): array
    {
        $schema = SectionSchema::homepageSection($model->key);

        return $model->toArray() + ['schema_label' => $schema['label'] ?? $model->name, 'note' => $schema['note'] ?? null];
    }
}
