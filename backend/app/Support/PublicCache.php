<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for public API payloads. Any content change bumps the version,
 * which invalidates every cached payload at once. Works with the file cache on
 * shared hosting, which has no cache tags.
 */
class PublicCache
{
    private const VERSION_KEY = 'public-cache-version';

    public static function remember(string $key, Closure $callback, int $ttlSeconds = 86400): mixed
    {
        return Cache::remember(self::versioned($key), $ttlSeconds, $callback);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 1) + 1);
    }

    private static function versioned(string $key): string
    {
        return 'public:v'.Cache::get(self::VERSION_KEY, 1).':'.$key;
    }
}
