<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Destination;
use App\Models\University;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);
    }

    public function test_sitemap_lists_published_indexable_content_only(): void
    {
        $uk = Destination::query()->create(['name' => 'United Kingdom', 'slug' => 'uk', 'status' => 'published']);
        Destination::query()->create(['name' => 'Hidden', 'slug' => 'hidden-land', 'status' => 'draft']);
        University::query()->create(['destination_id' => $uk->id, 'name' => 'Oxford', 'slug' => 'oxford', 'type' => 'public', 'status' => 'published']);
        $noindex = University::query()->create(['destination_id' => $uk->id, 'name' => 'Secret', 'slug' => 'secret-uni', 'type' => 'public', 'status' => 'published']);
        $noindex->seo()->create(['robots' => 'noindex, nofollow']);
        Blog::query()->create(['title' => 'Future post', 'slug' => 'future-post', 'status' => 'published', 'published_at' => now()->addWeek()]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'Sitemap is valid XML');
        $this->assertStringContainsString('<loc>http://localhost/study-in-uk</loc>', $xml);
        $this->assertStringContainsString('<loc>http://localhost/universities/oxford</loc>', $xml);
        $this->assertStringContainsString('<loc>http://localhost/book-appointment</loc>', $xml);
        $this->assertStringContainsString('<loc>http://localhost/gallery/videos</loc>', $xml);
        foreach (['hidden-land', 'secret-uni', 'future-post', '/appointment<'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $xml);
        }
    }

    public function test_sitemap_refreshes_after_content_changes(): void
    {
        $this->get('/sitemap.xml')->assertDontSee('study-in-france', false);
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->postJson('/api/v1/admin/destinations', ['name' => 'France', 'slug' => 'france', 'status' => 'published'])->assertCreated();
        $this->get('/sitemap.xml')->assertSee('<loc>http://localhost/study-in-france</loc>', false);
    }

    public function test_homepage_has_organisation_schema_and_robots_points_to_sitemap(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"EducationalOrganization"', $html);
        $this->assertStringContainsString('"@type":"SearchAction"', $html);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /appointment/')
            ->assertSee('Sitemap: http://localhost/sitemap.xml');
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Security-Policy', "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
        $this->getJson('/api/v1/site')->assertHeader('X-Content-Type-Options', 'nosniff');

        // HTTPS-only headers.
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security')->assertHeaderMissing('Cross-Origin-Opener-Policy');
        $this->get('https://localhost/')->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Strict-Transport-Security')
            ->assertHeader('Content-Security-Policy', "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; upgrade-insecure-requests");
    }
}
