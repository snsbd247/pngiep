<?php

namespace Database\Seeders\Concerns;

/**
 * Starter photos (Unsplash, free licence) used until the admin uploads their own.
 */
trait StockImages
{
    protected function photo(string $id, int $width = 1200): string
    {
        return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$width}&q=80";
    }
}
