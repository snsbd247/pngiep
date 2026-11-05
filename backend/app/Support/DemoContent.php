<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Course;
use App\Models\GalleryAlbum;
use App\Models\GalleryVideo;
use App\Models\SuccessStory;
use App\Models\Testimonial;
use App\Models\University;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Demo content created by DemoDirectorySeeder is listed in a manifest so it can be
 * removed in one step once the real content is in place (php artisan app:demo-content remove).
 */
class DemoContent
{
    /** manifest key => model class */
    public const MODELS = [
        'courses' => Course::class,
        'success_stories' => SuccessStory::class,
        'testimonials' => Testimonial::class,
        'gallery_albums' => GalleryAlbum::class,
        'gallery_videos' => GalleryVideo::class,
        'blogs' => Blog::class,
    ];

    public static function manifestPath(): string
    {
        return storage_path('app/demo-content.json');
    }

    public static function saveManifest(array $created): void
    {
        file_put_contents(self::manifestPath(), json_encode($created, JSON_PRETTY_PRINT));
    }

    /** @return array<string, list<int>> */
    public static function manifest(): array
    {
        $path = self::manifestPath();

        return is_file($path) ? (array) json_decode((string) file_get_contents($path), true) : [];
    }

    /** Remaining demo records per type (records an admin already deleted are not counted). */
    public static function counts(): array
    {
        $manifest = self::manifest();
        $counts = [];
        foreach (self::MODELS as $key => $class) {
            $counts[$key] = $class::query()->whereKey($manifest[$key] ?? [])->count();
        }
        $counts['university_fees'] = count($manifest['university_fees'] ?? []);

        return $counts;
    }

    /** Deletes every remaining demo record. Returns how many records were removed. */
    public static function remove(): int
    {
        $manifest = self::manifest();
        $removed = 0;

        DB::transaction(function () use ($manifest, &$removed) {
            foreach (self::MODELS as $key => $class) {
                $class::query()->whereKey($manifest[$key] ?? [])->get()->each(function (Model $model) use (&$removed) {
                    if ($model instanceof GalleryAlbum) {
                        $model->images()->delete();
                    }
                    if ($model instanceof Blog) {
                        $model->tags()->detach();
                    }
                    method_exists($model, 'forceDelete') ? $model->forceDelete() : $model->delete();
                    $removed++;
                });
            }
            // Sample fee ranges that were written onto the real partner universities.
            University::query()->whereKey($manifest['university_fees'] ?? [])->update(['tuition_fee_min' => null, 'tuition_fee_max' => null]);
        });

        @unlink(self::manifestPath());
        PublicCache::flush();

        return $removed;
    }
}
