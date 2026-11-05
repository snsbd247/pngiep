<?php

namespace App\Http\Controllers\Api\V1\Admin\Terms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Terms\TermRequest;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Partner;
use App\Models\SuccessStory;
use App\Models\Term;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * One admin-managed list. Subclasses only name their taxonomy; each is routed under the
 * permission of the module it belongs to (see routes/api.php).
 */
abstract class TermController extends CrudController
{
    protected string $taxonomy;

    protected string $model = Term::class;

    protected string $request = TermRequest::class;

    protected array $searchable = ['name'];

    protected array $filters = ['is_active' => 'is_active'];

    /** Where records use this list: [model, column, stores] (stores: id | name). */
    private const USAGE = [
        'page_category' => [Page::class, 'page_category_id'],
        'destination_category' => [Destination::class, 'destination_category_id'],
        'story_category' => [SuccessStory::class, 'story_category_id'],
        'partner_category' => [Partner::class, 'category'],
        'faq_category' => [Faq::class, 'category'],
        'degree_type' => [\App\Models\Course::class, 'degree_type'],
    ];

    protected function query(): Builder
    {
        return Term::query()->of($this->taxonomy);
    }

    protected function prepare(array $data, ?Model $model): array
    {
        $data = parent::prepare($data, $model);
        $data['taxonomy'] = $this->taxonomy;
        if (! $model) {
            $data['sort_order'] = (int) Term::query()->of($this->taxonomy)->max('sort_order') + 1;
        }

        return $data;
    }

    /** Renaming a name-based entry renames it on every record that uses it. */
    protected function afterSave(Model $model, FormRequest $request): void
    {
        parent::afterSave($model, $request);
        /** @var Term $model */
        $usage = self::USAGE[$this->taxonomy] ?? null;
        $old = $model->getPrevious()['name'] ?? null;
        if ($usage && Term::TAXONOMIES[$this->taxonomy]['stores'] === 'name' && $model->wasChanged('name') && $old) {
            [$class, $column] = $usage;
            $class::query()->where($column, $old)->update([$column => $model->name]);
        }
        if ($this->taxonomy === 'intake' && $model->wasChanged('name') && $old) {
            $this->renameIntake($old, $model->name);
        }
        PublicCache::flush();
    }

    protected function transform(Model $model, bool $detail = false): array
    {
        $data = $model->toArray();
        $data['usage_count'] = $this->usage($model);

        return $data;
    }

    private function usage(Term $term): int
    {
        if ($this->taxonomy === 'intake') {
            return \App\Models\Course::query()->whereJsonContains('intakes', $term->name)->count();
        }
        [$class, $column] = self::USAGE[$this->taxonomy];

        return $class::query()->where($column, Term::TAXONOMIES[$this->taxonomy]['stores'] === 'id' ? $term->id : $term->name)->count();
    }

    private function renameIntake(string $old, string $new): void
    {
        foreach ([\App\Models\Course::class, \App\Models\University::class, \App\Models\Destination::class] as $class) {
            DB::transaction(fn () => $class::query()->whereJsonContains('intakes', $old)->get()->each(function ($row) use ($old, $new) {
                $row->update(['intakes' => array_values(array_map(fn ($i) => $i === $old ? $new : $i, $row->intakes ?? []))]);
            }));
        }
    }
}
