<?php

namespace App\Support;

use Stevebauman\Purify\Facades\Purify;

/**
 * Turns the field definitions in config/sections.php into validation rules
 * and sanitises the validated data (HTML is purified, unknown keys dropped).
 */
class SectionSchema
{
    public static function pageType(string $type): ?array
    {
        return config("sections.page_types.{$type}");
    }

    public static function homepageSection(string $key): ?array
    {
        return config("sections.homepage.{$key}");
    }

    /** Laravel rules for `data.*` from a field list. */
    public static function rules(array $fields, string $prefix = 'data'): array
    {
        $rules = [$prefix => ['nullable', 'array']];

        foreach ($fields as $field) {
            $key = "{$prefix}.{$field['name']}";
            $required = ! empty($field['required']) ? 'required' : 'nullable';

            $rules[$key] = match ($field['type']) {
                'html' => [$required, 'string', 'max:100000'],
                'textarea' => [$required, 'string', 'max:5000'],
                'number' => [$required, 'numeric', 'min:0', 'max:100000'],
                'toggle' => ['nullable', 'boolean'],
                'select' => [$required, 'in:'.implode(',', array_column($field['options'] ?? [], 'value'))],
                'icon' => [$required, 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'],
                'url', 'image' => [$required, 'string', 'max:500', new SafeUrl],
                'images' => ['nullable', 'array', 'max:'.($field['max'] ?? 20)],
                'repeater' => ['nullable', 'array', 'max:'.($field['max'] ?? 50)],
                default => [$required, 'string', 'max:500'],
            };

            if ($field['type'] === 'images') {
                $rules["{$key}.*"] = ['string', 'max:500', new SafeUrl];
            }

            if ($field['type'] === 'repeater') {
                $rules["{$key}.*"] = ['array'];
                $rules = array_merge($rules, array_diff_key(self::rules($field['fields'], "{$key}.*"), ["{$key}.*" => true]));
            }
        }

        return $rules;
    }

    /**
     * Rules for an ordered `sections` list where each item's `data` is validated
     * against its own type schema (used by pages, destinations, universities, services).
     */
    public static function listRules(array $sections, string $key = 'sections'): array
    {
        $rules = [
            $key => ['nullable', 'array', 'max:40'],
            "{$key}.*.id" => ['nullable', 'integer'],
            "{$key}.*.type" => ['required', 'in:'.implode(',', array_keys(config('sections.page_types')))],
            "{$key}.*.eyebrow" => ['nullable', 'string', 'max:120'],
            "{$key}.*.title" => ['nullable', 'string', 'max:190'],
            "{$key}.*.subtitle" => ['nullable', 'string', 'max:1000'],
            "{$key}.*.is_active" => ['boolean'],
        ];

        foreach ($sections as $i => $section) {
            $schema = self::pageType((string) ($section['type'] ?? ''));
            if ($schema) {
                $rules = array_merge($rules, self::rules($schema['fields'], "{$key}.{$i}.data"));
            }
        }

        return $rules;
    }

    /** Keep only defined keys and purify HTML fields. */
    public static function clean(array $fields, ?array $data): array
    {
        $clean = [];

        foreach ($fields as $field) {
            $value = $data[$field['name']] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $clean[$field['name']] = match ($field['type']) {
                'html' => Purify::clean($value),
                'repeater' => array_values(array_map(fn ($item) => self::clean($field['fields'], (array) $item), (array) $value)),
                'images' => array_values(array_filter((array) $value)),
                'toggle' => (bool) $value,
                'number' => $value + 0,
                default => $value,
            };
        }

        return $clean;
    }
}
