<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Destination;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Term;
use Illuminate\Database\Seeder;

/**
 * Starter entries for the admin-managed lists, plus categories for existing pages and
 * destinations that have none. Idempotent: existing entries and choices are kept, and
 * values already used on records (degree types, FAQ / partner categories) are added.
 */
class TermSeeder extends Seeder
{
    private const DEFAULTS = [
        'page_category' => ['Company', 'Website Sections', 'Legal'],
        'destination_category' => ['Asia', 'Middle East', 'Oceania', 'Europe', 'North America'],
        'story_category' => ['Admission', 'Scholarship', 'Visa Success'],
        'partner_category' => ['University', 'College', 'Government', 'Platform'],
        'faq_category' => ['General', 'Destinations', 'Universities', 'Services', 'Scholarships', 'Visa'],
        'degree_type' => ['Diploma', 'BBA', 'BCA', 'B.Sc', 'B.Tech', 'MBA', 'M.Tech', 'PhD'],
        'intake' => ['January', 'February', 'March', 'April', 'July', 'August', 'September', 'October'],
    ];

    public function run(): void
    {
        $values = self::DEFAULTS;
        // Values already in use become list entries, so nothing in use disappears from the dropdowns.
        $values['degree_type'] = array_merge($values['degree_type'], Course::query()->whereNotNull('degree_type')->distinct()->pluck('degree_type')->all());
        $values['faq_category'] = array_merge($values['faq_category'], Faq::query()->whereNotNull('category')->distinct()->pluck('category')->all());
        $values['partner_category'] = array_merge($values['partner_category'], Partner::query()->whereNotNull('category')->distinct()->pluck('category')
            ->map(fn ($c) => ucfirst($c))->all());

        foreach ($values as $taxonomy => $names) {
            foreach (array_values(array_unique(array_filter($names))) as $i => $name) {
                Term::query()->of($taxonomy)->where('name', $name)->exists()
                    || Term::query()->create(['taxonomy' => $taxonomy, 'name' => $name, 'sort_order' => $i]);
            }
        }

        // Partner categories were stored lower-case ("university"): align them with the list.
        Partner::query()->whereNotNull('category')->get()->each(fn ($p) => $p->update(['category' => ucfirst($p->category)]));

        $id = fn (string $taxonomy, string $name) => Term::query()->of($taxonomy)->where('name', $name)->value('id');

        Page::query()->whereNull('page_category_id')->get()->each(function (Page $page) use ($id) {
            $category = match (true) {
                $page->template === 'listing' => 'Website Sections',
                in_array($page->slug, ['privacy-policy', 'terms-and-conditions'], true) => 'Legal',
                default => 'Company',
            };
            $page->update(['page_category_id' => $id('page_category', $category)]);
        });

        $regions = ['in' => 'Asia', 'ae' => 'Middle East', 'au' => 'Oceania', 'gb' => 'Europe', 'ie' => 'Europe', 'de' => 'Europe', 'fr' => 'Europe', 'us' => 'North America', 'ca' => 'North America'];
        Destination::query()->whereNull('destination_category_id')->get()->each(function (Destination $d) use ($regions, $id) {
            if ($region = $regions[strtolower((string) $d->country_code)] ?? null) {
                $d->update(['destination_category_id' => $id('destination_category', $region)]);
            }
        });
    }
}
