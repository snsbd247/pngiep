<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Course;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Models\SuccessStory;
use App\Models\Testimonial;
use App\Models\University;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoDirectorySeeder;
use Database\Seeders\PartnerDirectorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DemoContentTest extends TestCase
{
    use RefreshDatabase;

    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep the real storage/app/demo-content.json untouched.
        $this->storage = sys_get_temp_dir().'/demo-content-test-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/app');
        $this->app->useStoragePath($this->storage);

        $this->seedFoundation();
        $this->seed([ContentSeeder::class, PartnerDirectorySeeder::class, DemoDirectorySeeder::class]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    public function test_demo_content_is_tracked_and_removed_without_touching_real_content(): void
    {
        $this->assertGreaterThan(0, Course::query()->count());
        $this->assertGreaterThan(0, SuccessStory::query()->count());
        $this->assertNotNull(University::query()->where('slug', 'parul-university')->value('tuition_fee_min'));

        // Real content added by an admin must survive.
        $realStory = SuccessStory::query()->create(['student_name' => 'Real Student', 'status' => 'published', 'quote' => 'Real.']);

        $this->artisan('app:demo-content')->assertSuccessful();
        $this->artisan('app:demo-content', ['action' => 'remove', '--force' => true])->assertSuccessful();

        $this->assertSame(0, Course::query()->count());
        $this->assertSame(0, Testimonial::query()->count());
        $this->assertSame(0, GalleryAlbum::query()->count());
        $this->assertSame(0, GalleryImage::query()->count());
        $this->assertSame(0, Blog::query()->count());
        $this->assertSame([$realStory->id], SuccessStory::query()->pluck('id')->all());

        // The real partner universities stay, without the sample fee ranges.
        $this->assertSame(6, University::query()->count());
        $this->assertNull(University::query()->where('slug', 'parul-university')->value('tuition_fee_min'));
        $this->assertFileDoesNotExist($this->storage.'/app/demo-content.json');

        // Seeding again does not bring the demo back once a manifest-free site has real courses.
        Course::query()->create(['university_id' => University::query()->value('id'), 'name' => 'Real Course', 'study_level' => 'undergraduate', 'status' => 'published']);
        $this->seed(DemoDirectorySeeder::class);
        $this->assertSame(1, Course::query()->count());
    }
}
