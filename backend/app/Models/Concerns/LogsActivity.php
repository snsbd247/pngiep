<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;
use Illuminate\Support\Str;

/**
 * Records create / update / delete of admin-managed models in the audit log.
 * Only runs when an authenticated user performs the change (public submissions are not logged here).
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            static::$event(function ($model) use ($event) {
                if (! auth()->check()) {
                    return;
                }

                $changes = $event === 'updated'
                    ? array_keys(array_diff_key($model->getChanges(), array_flip(['updated_at'])))
                    : null;

                if ($event === 'updated' && empty($changes)) {
                    return;
                }

                app(ActivityLogger::class)->log(
                    action: $event,
                    module: $model->activityModule(),
                    subject: $model,
                    description: Str::headline(class_basename($model))." #{$model->getKey()} {$event}",
                    properties: $changes ? ['fields' => $changes] : null,
                );
            });
        }
    }

    public function activityModule(): string
    {
        return Str::snake(Str::pluralStudly(class_basename($this)));
    }
}
