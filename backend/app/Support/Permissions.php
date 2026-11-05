<?php

namespace App\Support;

class Permissions
{
    /** @return array<string, string> slug => label */
    public static function all(): array
    {
        return array_merge(...array_values(config('permissions.modules')));
    }

    /** @return array<string, string> slug => module */
    public static function modules(): array
    {
        $map = [];
        foreach (config('permissions.modules') as $module => $permissions) {
            foreach (array_keys($permissions) as $slug) {
                $map[$slug] = $module;
            }
        }

        return $map;
    }

    /** Resolve a role definition like ['*', '!roles.manage'] into concrete slugs. */
    public static function resolve(array $rules): array
    {
        $all = array_keys(self::all());
        $granted = in_array('*', $rules, true) ? $all : array_values(array_intersect($rules, $all));
        $denied = array_map(fn ($r) => ltrim($r, '!'), array_filter($rules, fn ($r) => str_starts_with($r, '!')));

        return array_values(array_diff($granted, $denied));
    }
}
