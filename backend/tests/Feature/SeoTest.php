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
        $this->assertStringContainsString('"@type":["EducationalOrganization","LocalBusiness"]', $html);
        $this->assertStringContainsString('"@type":"SearchAction"', $html);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /appointment/')
            ->assertSee('Sitemap: http://localhost/sitemap.xml');
    }

    public function test_security_headers_are_sent(): void
    {
        $csp = $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->headers->get('Content-Security-Policy');
        foreach (["default-src 'self'", "'strict-dynamic'", "object-src 'none'", "base-uri 'self'", "form-action 'self'", "frame-ancestors 'self'"] as $directive) {
            $this->assertStringContainsString($directive, $csp);
        }
        $this->assertStringNotContainsString('upgrade-insecure-requests', $csp);
        $this->getJson('/api/v1/site')->assertHeader('X-Content-Type-Options', 'nosniff');

        // HTTPS-only headers.
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security')->assertHeaderMissing('Cross-Origin-Opener-Policy');
        $https = $this->get('https://localhost/')->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')->assertHeader('Strict-Transport-Security');
        $this->assertStringEndsWith('; upgrade-insecure-requests', $https->headers->get('Content-Security-Policy'));
    }

    public function test_detail_pages_get_breadcrumbs_and_titles_fit_search_results(): void
    {
        $uk = Destination::query()->create(['name' => 'United Kingdom', 'slug' => 'uk', 'status' => 'published']);
        University::query()->create(['destination_id' => $uk->id, 'name' => 'Oxford', 'slug' => 'oxford', 'type' => 'public', 'status' => 'published']);
        $long = 'Bachelor of Business Administration with Honours in International Management';
        University::query()->create(['destination_id' => $uk->id, 'name' => $long, 'slug' => 'long-name', 'type' => 'public', 'status' => 'published']);

        $html = $this->get('/universities/oxford')->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"CollegeOrUniversity"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"name":"Universities","item":"http://localhost/universities"', $html);
        $this->assertMatchesRegularExpression('#<title>Oxford \| [^<]+</title>#', $html);

        // Too long even with the short name: the page name alone.
        $this->get('/universities/long-name')->assertSee("<title>{$long}</title>", false);
        $title = app(\App\Services\SeoService::class)->fitTitle('Study in Australia from Papua New Guinea | PNG International Education Portal', 'PNG International Education Portal');
        $this->assertSame('Study in Australia from Papua New Guinea | PNGIEP', $title);
    }

    public function test_course_schema_has_fee_duration_and_credential(): void
    {
        $uk = Destination::query()->create(['name' => 'United Kingdom', 'slug' => 'uk', 'status' => 'published']);
        $uni = University::query()->create(['destination_id' => $uk->id, 'name' => 'Oxford', 'slug' => 'oxford', 'type' => 'public', 'status' => 'published']);
        \App\Models\Course::query()->create([
            'university_id' => $uni->id, 'name' => 'MBA', 'slug' => 'mba-oxford', 'study_level' => 'postgraduate', 'status' => 'published',
            'degree_type' => 'MBA', 'duration_months' => 24, 'tuition_fee' => 45000, 'currency_code' => 'gbp', 'fee_period' => 'year',
        ]);

        $html = $this->get('/courses/mba-oxford')->assertOk()->getContent();
        $this->assertStringContainsString('"@type":"Course"', $html);
        $this->assertStringContainsString('"offers":{"@type":"Offer","category":"Paid","price":"45000","priceCurrency":"GBP","description":"Tuition fee per year"}', $html);
        $this->assertStringContainsString('"timeRequired":"P24M"', $html);
        $this->assertStringContainsString('"educationalCredentialAwarded":"MBA"', $html);
        $this->assertStringContainsString('"url":"http://localhost/universities/oxford"', $html);
    }

    public function test_local_business_schema_search_noindex_file_404s_and_clean_sitemap(): void
    {
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('"@type":["EducationalOrganization","LocalBusiness"]', $home);
        $this->assertStringContainsString('"@type":"PostalAddress"', $home);

        $this->get('/search')->assertOk()->assertSee('content="noindex, follow"', false);
        $this->get('/wp-login.php')->assertNotFound();
        $this->get('/composer.json')->assertNotFound();

        $sitemap = $this->get('/sitemap.xml')->assertOk()->assertHeaderMissing('Set-Cookie');
        $this->assertMatchesRegularExpression('#<loc>http://localhost</loc><lastmod>[^<]+</lastmod>#', $sitemap->getContent());
        // Google renders the React pages, whose content comes from the public API: it must not be blocked.
        $this->get('/robots.txt')->assertOk()->assertHeaderMissing('Set-Cookie')
            ->assertDontSee("Disallow: /api/\n", false)
            ->assertSee('Disallow: /api/v1/admin/')
            ->assertSee('Allow: /api/v1/');
    }
}
