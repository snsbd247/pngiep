<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_large_images_are_resized_and_converted_to_webp(): void
    {
        $user = $this->userWithRole('content-manager');

        $response = $this->actingAs($user)->post('/api/v1/admin/media', [
            'files' => [UploadedFile::fake()->image('Campus Photo.jpg', 3000, 1500)],
            'folder' => 'gallery',
        ], ['Accept' => 'application/json'])->assertCreated();

        $media = Media::query()->firstOrFail();
        $this->assertSame('gallery', $media->folder);
        $this->assertSame(2000, $media->width);

        if (function_exists('imagewebp')) {
            $this->assertSame('image/webp', $media->mime_type);
            $this->assertStringEndsWith('.webp', $media->path);
        }

        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->variants['thumb']);
        $response->assertJsonPath('data.0.id', $media->id);
    }

    public function test_disguised_scripts_and_disallowed_types_are_rejected(): void
    {
        $user = $this->userWithRole('content-manager');

        foreach ([
            UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]); ?>'),
            UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ] as $file) {
            $this->actingAs($user)->post('/api/v1/admin/media', ['files' => [$file]], ['Accept' => 'application/json'])
                ->assertStatus(422);
        }

        $this->assertSame(0, Media::query()->count());
    }

    public function test_deleting_media_removes_files(): void
    {
        $user = $this->userWithRole('content-manager');
        $this->actingAs($user)->post('/api/v1/admin/media', ['files' => [UploadedFile::fake()->image('a.png', 800, 600)]], ['Accept' => 'application/json']);
        $media = Media::query()->firstOrFail();

        $this->actingAs($user)->deleteJson("/api/v1/admin/media/{$media->id}")->assertOk();

        Storage::disk('public')->assertMissing($media->path);
        $this->assertModelMissing($media);
    }
}
