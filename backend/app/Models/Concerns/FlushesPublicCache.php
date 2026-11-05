<?php

namespace App\Models\Concerns;

use App\Support\PublicCache;

/**
 * Invalidates cached public API payloads whenever website content changes.
 */
trait FlushesPublicCache
{
    public static function bootFlushesPublicCache(): void
    {
        foreach (['saved', 'deleted', 'restored'] as $event) {
            if ($event === 'restored' && ! method_exists(static::class, 'restore')) {
                continue;
            }
            static::$event(fn () => PublicCache::flush());
        }
    }
}
