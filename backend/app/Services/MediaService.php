<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Stores uploads on the public disk and optimises raster images:
 * - resizes anything wider than MAX_WIDTH
 * - converts JPEG/PNG to WebP when the server's GD supports it
 * - generates a small thumbnail variant for admin grids and cards
 */
class MediaService
{
    public const MAX_WIDTH = 2000;

    public const THUMB_WIDTH = 480;

    public const QUALITY = 82;

    /** Raster types we re-encode. GIF (animation) and PDF are stored untouched. SVG is never accepted (XSS risk). */
    private const OPTIMISABLE = ['image/jpeg', 'image/png', 'image/webp'];

    public function store(UploadedFile $file, string $folder = 'general', ?int $userId = null, array $attributes = []): Media
    {
        $folder = Str::slug($folder) ?: 'general';
        $directory = 'media/'.$folder.'/'.now()->format('Y/m');
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $baseName = Str::limit($baseName, 60, '').'-'.Str::lower(Str::random(6));

        $width = $height = null;
        $variants = null;

        if (in_array($mime, self::OPTIMISABLE, true) && $this->canProcessImages()) {
            try {
                [$path, $mime, $width, $height, $variants] = $this->storeOptimisedImage($file, $directory, $baseName);
            } catch (Throwable $e) {
                // Corrupt or disguised file that claimed to be an image.
                report($e);
                throw ValidationException::withMessages([
                    'files' => "\"{$file->getClientOriginalName()}\" is not a valid image file.",
                ]);
            }
        } else {
            $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $path = $file->storeAs($directory, "{$baseName}.{$extension}", 'public');
            if (str_starts_with($mime, 'image/')) {
                [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];
            }
        }

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'folder' => $folder,
            'path' => $path,
            'filename' => basename($path),
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $mime,
            'extension' => pathinfo($path, PATHINFO_EXTENSION),
            'size' => Storage::disk('public')->size($path),
            'width' => $width,
            'height' => $height,
            'variants' => $variants,
            'uploaded_by' => $userId,
            'alt' => $attributes['alt'] ?? null,
            'title' => $attributes['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
        ]);
    }

    public function delete(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $disk->delete(array_filter([$media->path, ...array_values($media->variants ?? [])]));
        $media->delete();
    }

    private function storeOptimisedImage(UploadedFile $file, string $directory, string $baseName): array
    {
        $manager = new ImageManager(new Driver);
        $useWebp = function_exists('imagewebp');
        $extension = $useWebp ? 'webp' : ($file->getMimeType() === 'image/png' ? 'png' : 'jpg');
        $mime = match ($extension) {
            'webp' => 'image/webp',
            'png' => 'image/png',
            default => 'image/jpeg',
        };

        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: self::MAX_WIDTH);
        $path = "{$directory}/{$baseName}.{$extension}";
        Storage::disk('public')->put($path, (string) $this->encode($image, $extension));

        $width = $image->width();
        $height = $image->height();

        $thumb = $manager->read($file->getRealPath())->scaleDown(width: self::THUMB_WIDTH);
        $thumbPath = "{$directory}/{$baseName}-thumb.{$extension}";
        Storage::disk('public')->put($thumbPath, (string) $this->encode($thumb, $extension));

        return [$path, $mime, $width, $height, ['thumb' => $thumbPath]];
    }

    private function encode($image, string $extension)
    {
        return match ($extension) {
            'webp' => $image->toWebp(self::QUALITY),
            'png' => $image->toPng(),
            default => $image->toJpeg(self::QUALITY),
        };
    }

    private function canProcessImages(): bool
    {
        return extension_loaded('gd');
    }
}
