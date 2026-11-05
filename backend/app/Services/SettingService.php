<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Cached access to the key/value settings table.
 */
class SettingService
{
    private const CACHE_KEY = 'settings.all';

    /** Setting types whose value is a storage path that should be exposed as an absolute URL. */
    private const FILE_TYPES = ['image', 'file'];

    private ?Collection $settings = null;

    /** @return Collection<string, Setting> keyed by setting key */
    public function all(): Collection
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        try {
            return $this->settings = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->orderBy('sort_order')->get()->keyBy('key'));
        } catch (Throwable) {
            // Before migrations run (e.g. during install) there is no settings table yet.
            return $this->settings = collect();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->all()->get($key);
        $value = $setting?->typedValue();

        return ($value === null || $value === '') ? $default : $value;
    }

    /** Public, non-sensitive settings for the website, grouped: ['general' => ['site_name' => ...]]. */
    public function publicSettings(): array
    {
        return $this->all()
            ->filter(fn (Setting $s) => $s->is_public && ! $s->is_encrypted)
            ->groupBy('group')
            ->map(fn (Collection $group) => $group->mapWithKeys(fn (Setting $s) => [$s->key => $this->present($s)]))
            ->toArray();
    }

    /** All settings for the admin form, grouped. Encrypted values are never sent back. */
    public function adminSettings(): array
    {
        return $this->all()
            ->groupBy('group')
            ->map(fn (Collection $group) => $group->map(fn (Setting $s) => [
                'key' => $s->key,
                'label' => $s->label,
                'help' => $s->help,
                'type' => $s->type,
                'options' => $s->options,
                'is_public' => $s->is_public,
                'value' => $s->is_encrypted ? null : $s->typedValue(),
                'url' => in_array($s->type, self::FILE_TYPES, true) ? $this->present($s) : null,
                'has_value' => $s->is_encrypted ? filled($s->value) : null,
            ])->values())
            ->toArray();
    }

    /**
     * Update many settings at once. Unknown keys are ignored.
     * Empty values for encrypted fields keep the existing secret.
     *
     * @return list<string> keys that changed
     */
    public function update(array $values): array
    {
        $changed = [];
        $settings = Setting::query()->whereIn('key', array_keys($values))->get();

        foreach ($settings as $setting) {
            $value = $values[$setting->key];
            if ($setting->is_encrypted && blank($value)) {
                continue;
            }
            $setting->setTypedValue($value);
            if ($setting->isDirty('value')) {
                $setting->save();
                $changed[] = $setting->key;
            }
        }

        $this->flush();

        return $changed;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->settings = null;
    }

    private function present(Setting $setting): mixed
    {
        $value = $setting->typedValue();

        if (in_array($setting->type, self::FILE_TYPES, true) && filled($value)) {
            return str_starts_with($value, 'http') ? $value : Storage::disk('public')->url($value);
        }

        return $value;
    }

    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }
}
