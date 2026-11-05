<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogger
{
    public function log(string $action, string $module, ?Model $subject = null, ?string $description = null, ?array $properties = null, ?int $userId = null): void
    {
        try {
            $request = request();

            ActivityLog::query()->create([
                'user_id' => $userId ?? auth()->id(),
                'action' => $action,
                'module' => $module,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'properties' => $properties,
                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) $request?->userAgent(), 490, ''),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Auditing must never break the user's action.
            report($e);
        }
    }
}
