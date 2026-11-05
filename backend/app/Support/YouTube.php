<?php

namespace App\Support;

/**
 * Extracts the 11-character video ID from YouTube URLs:
 * youtube.com/watch?v=ID, youtu.be/ID, youtube.com/shorts/ID, /embed/ID, /live/ID, m.youtube.com, youtube-nocookie.com.
 */
class YouTube
{
    private const PATTERN = '~^(?:https?://)?(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~i';

    public static function id(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return preg_match(self::PATTERN, trim($url), $m) ? $m[1] : null;
    }

    public static function isValid(?string $url): bool
    {
        return self::id($url) !== null;
    }
}
