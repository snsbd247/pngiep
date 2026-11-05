<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\PublicCache;
use App\Support\SectionSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stevebauman\Purify\Facades\Purify;

/**
 * Generic admin CRUD for simple content modules.
 *
 * A module controller only declares its model, FormRequest and list options:
 *
 *   class PartnerController extends CrudController {
 *       protected string $model = Partner::class;
 *       protected string $request = PartnerRequest::class;
 *       protected array $searchable = ['name'];
 *   }
 *
 * Routes are registered with the Route::crud() macro (see AppServiceProvider).
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** @var class-string<FormRequest> */
    protected string $request;

    /** Columns matched by ?search= */
    protected array $searchable = [];

    /** Query string filters: param => column (exact match). */
    protected array $filters = ['status' => 'status'];

    /** Relations loaded for list and detail. */
    protected array $with = [];

    /** Whether the table has sort_order (enables drag & drop reorder). */
    protected bool $sortable = true;

    /** Whether the model has a polymorphic SEO record. */
    protected bool $hasSeo = false;

    /** Whether the model has an ordered section list (HasSections) edited with the record. */
    protected bool $hasSections = false;

    /** Rich-text attributes that are purified (XSS-safe) before saving. */
    protected array $htmlFields = [];

    public function index(Request $request): JsonResponse
    {
        $params = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = $this->query()->with($this->with);

        if ($term = $params['search'] ?? null) {
            $query->where(function (Builder $q) use ($term) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        foreach ($this->filters as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->input($param));
            }
        }

        $this->applyOrder($query);
        $page = $query->paginate($params['per_page'] ?? 20);
        $page->setCollection($page->getCollection()->map(fn (Model $m) => $this->transform($m)));

        return ApiResponse::success($page->items(), meta: [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'from' => $page->firstItem(),
            'to' => $page->lastItem(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return ApiResponse::success($this->transform($this->find($id), detail: true));
    }

    public function store(): JsonResponse
    {
        $request = app($this->request);

        $model = DB::transaction(function () use ($request) {
            $model = $this->model::query()->create($this->prepare($request->validated(), null));
            $this->afterSave($model, $request);

            return $model;
        });

        return ApiResponse::created($this->transform($this->find($model->getKey()), detail: true));
    }

    public function update(int $id): JsonResponse
    {
        $model = $this->find($id);
        $request = app($this->request);

        DB::transaction(function () use ($model, $request) {
            $model->update($this->prepare($request->validated(), $model));
            $this->afterSave($model, $request);
        });

        return ApiResponse::success($this->transform($this->find($id), detail: true), 'Saved successfully.');
    }

    public function destroy(int $id): JsonResponse
    {
        $model = $this->find($id);
        $this->beforeDelete($model);
        $model->delete();

        return ApiResponse::success(null, 'Deleted successfully.');
    }

    /** Persist a new order: { ids: [3, 1, 2] } → sort_order 0, 1, 2. */
    public function reorder(Request $request): JsonResponse
    {
        abort_unless($this->sortable, 404);
        $ids = $request->validate(['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer']])['ids'];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $position => $id) {
                $this->model::query()->whereKey($id)->update(['sort_order' => $position]);
            }
        });
        $this->afterReorder();

        return ApiResponse::success(null, 'Order saved.');
    }

    // ------------------------------------------------------------------ hooks

    protected function query(): Builder
    {
        return $this->model::query();
    }

    protected function applyOrder(Builder $query): void
    {
        $this->sortable ? $query->orderBy('sort_order')->orderBy('id') : $query->latest('id');
    }

    protected function find(int $id): Model
    {
        return $this->query()->with(array_merge($this->with, $this->hasSeo ? ['seo'] : []))->findOrFail($id);
    }

    /** Map validated input to model attributes (strip non-column keys such as `seo`). */
    protected function prepare(array $data, ?Model $model): array
    {
        unset($data['seo'], $data['sections']);

        // New records go to the end of the admin-defined order.
        if (! $model && $this->sortable) {
            $data['sort_order'] = (int) $this->model::query()->max('sort_order') + 1;
        }

        foreach ($this->htmlFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = Purify::clean($data[$field]);
            }
        }

        return $data;
    }

    protected function afterSave(Model $model, FormRequest $request): void
    {
        if ($this->hasSeo && $request->has('seo')) {
            $model->syncSeo($request->validated('seo') ?? []);
        }

        if ($this->hasSections && $request->has('sections')) {
            SectionSync::sync($model, $request->validated('sections') ?? []);
        }
    }

    protected function afterReorder(): void
    {
        PublicCache::flush();
    }

    protected function beforeDelete(Model $model): void {}

    protected function transform(Model $model, bool $detail = false): array
    {
        $data = $model->toArray();
        if ($detail && $this->hasSections) {
            $data['sections'] = $model->sections()->get()->toArray();
        }

        return $data;
    }
}
