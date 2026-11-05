<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Saves an ordered list of typed sections for any model using HasSections
 * (pages, destinations, universities, services): updates by id, creates new
 * ones, deletes removed ones, and cleans `data` against the type's schema.
 */
class SectionSync
{
    public static function sync(Model $owner, array $sections): void
    {
        $keep = [];

        foreach (array_values($sections) as $position => $input) {
            $schema = SectionSchema::pageType($input['type']) ?? ['fields' => []];
            $attributes = [
                'type' => $input['type'],
                'key' => $input['type'].'-'.($position + 1),
                'eyebrow' => $input['eyebrow'] ?? null,
                'title' => $input['title'] ?? null,
                'subtitle' => $input['subtitle'] ?? null,
                'is_active' => $input['is_active'] ?? true,
                'sort_order' => $position,
                'data' => SectionSchema::clean($schema['fields'], $input['data'] ?? []),
            ];

            $section = ! empty($input['id']) ? $owner->sections()->whereKey($input['id'])->first() : null;
            $section ? $section->update($attributes) : $section = $owner->sections()->create($attributes);
            $keep[] = $section->id;
        }

        $owner->sections()->whereNotIn('id', $keep)->delete();
    }
}
