<?php

namespace App\Http\Requests\Admin;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules are derived from each setting's declared type, so new
 * settings added by a seeder are validated without code changes.
 */
class SettingsUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = ['settings' => ['required', 'array']];
        $settings = app(SettingService::class)->all();

        foreach (array_keys((array) $this->input('settings', [])) as $key) {
            $setting = $settings->get($key);
            if (! $setting) {
                $rules["settings.{$key}"] = ['prohibited'];

                continue;
            }

            $rules["settings.{$key}"] = match ($setting->type) {
                'boolean' => ['nullable', 'boolean'],
                'number' => ['nullable', 'numeric'],
                'email' => ['nullable', 'email', 'max:255'],
                'url' => ['nullable', 'url', 'max:500'],
                'color' => ['nullable', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
                'json' => ['nullable', 'array'],
                'select' => ['nullable', 'in:'.implode(',', array_column($setting->options ?? [], 'value'))],
                'code' => ['nullable', 'string', 'max:20000'],
                'textarea', 'html' => ['nullable', 'string', 'max:10000'],
                default => ['nullable', 'string', 'max:1000'],
            };

            if ($setting->type === 'json' && $setting->options) {
                $rules["settings.{$key}.*"] = ['in:'.implode(',', array_column($setting->options, 'value'))];
            }
        }

        return $rules;
    }

    public function attributes(): array
    {
        $settings = app(SettingService::class)->all();

        return collect((array) $this->input('settings', []))
            ->keys()
            ->mapWithKeys(fn ($key) => ["settings.{$key}" => $settings->get($key)?->label ?? $key])
            ->all();
    }
}
