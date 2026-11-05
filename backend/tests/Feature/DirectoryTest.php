<?php

namespace Tests\Feature;

use App\Enums\LeadSource;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Lead;
use App\Models\Page;
use App\Models\University;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    private Destination $uk;

    private Destination $de;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);

        $this->uk = Destination::query()->create(['name' => 'United Kingdom', 'slug' => 'uk', 'country_code' => 'gb', 'status' => 'published']);
        $this->de = Destination::query()->create(['name' => 'Germany', 'slug' => 'germany', 'country_code' => 'de', 'status' => 'published']);
    }

    private function university(Destination $d, array $attrs = []): University
    {
        return University::query()->create($attrs + ['destination_id' => $d->id, 'name' => 'Uni '.uniqid(), 'type' => 'public', 'status' => 'published']);
    }

    private function course(University $u, array $attrs = []): Course
    {
        return Course::query()->create($attrs + ['university_id' => $u->id, 'name' => 'Course '.uniqid(), 'study_level' => 'postgraduate', 'status' => 'published']);
    }

    public function test_admin_created_destination_appears_publicly_with_sections(): void
    {
        $admin = $this->userWithRole('content-manager');

        $this->actingAs($admin)->postJson('/api/v1/admin/destinations', [
            'name' => 'Canada', 'slug' => 'canada', 'country_code' => 'ca', 'status' => 'published',
            'quick_facts' => [['icon' => 'users', 'value' => '800,000+', 'label' => 'International Students']],
            'sections' => [
                ['type' => 'table', 'title' => 'Tuition', 'data' => ['width' => 'half', 'column_1' => 'Level', 'rows' => [['label' => 'UG', 'value' => 'C$20,000']]]],
                ['type' => 'related_universities', 'title' => 'Top universities'],
            ],
            'seo' => ['title' => 'Study in Canada'],
        ])->assertCreated();

        $this->getJson('/api/v1/destinations')->assertOk()->assertJsonFragment(['slug' => 'canada']);
        $this->getJson('/api/v1/destinations/canada')->assertOk()
            ->assertJsonPath('data.quick_facts.0.value', '800,000+')
            ->assertJsonPath('data.sections.0.data.rows.0.value', 'C$20,000')
            ->assertJsonPath('data.sections.1.universities', []);

        $this->get('/study-in-canada')->assertOk()->assertSee('<title>Study in Canada</title>', false);
    }

    public function test_drafts_are_hidden_and_unknown_slugs_return_404(): void
    {
        Destination::query()->create(['name' => 'Secret', 'slug' => 'secret', 'status' => 'draft']);

        $this->getJson('/api/v1/destinations/secret')->assertNotFound();
        $this->get('/study-in-secret')->assertNotFound();
        $this->get('/universities/nope')->assertNotFound();
    }

    public function test_old_destination_urls_redirect_permanently(): void
    {
        $this->get('/destinations/uk')->assertStatus(301)->assertRedirect(url('/study-in-uk'));
    }

    public function test_university_filters_sorting_and_facets(): void
    {
        $oxford = $this->university($this->uk, ['name' => 'Oxford', 'world_ranking' => 3, 'tuition_fee_min' => 30000]);
        $teesside = $this->university($this->uk, ['name' => 'Teesside', 'world_ranking' => 900, 'tuition_fee_min' => 13000]);
        $schiller = $this->university($this->de, ['name' => 'Schiller', 'type' => 'private', 'tuition_fee_min' => 12000]);
        $this->course($oxford, ['subject' => 'Law', 'study_level' => 'postgraduate']);
        $this->course($teesside, ['subject' => 'Computer Science', 'study_level' => 'undergraduate']);
        $this->university($this->uk, ['name' => 'Hidden', 'status' => 'draft']);

        $names = fn (string $query) => collect($this->getJson('/api/v1/universities?'.$query)->assertOk()->json('data'))->pluck('name')->all();

        $this->assertEqualsCanonicalizing(['Oxford', 'Teesside', 'Schiller'], $names(''));
        $this->assertSame(['Oxford', 'Teesside'], $names('destination[]=uk&sort=ranking'));
        $this->assertSame(['Schiller'], $names('type[]=private'));
        $this->assertSame(['Teesside'], $names('subject[]=Computer%20Science'));
        $this->assertSame(['Oxford'], $names('level[]=postgraduate'));
        $this->assertEqualsCanonicalizing(['Teesside', 'Schiller'], $names('fee_max=15000'));
        $this->assertSame(['Schiller', 'Teesside', 'Oxford'], $names('sort=fee_low'));

        $facets = $this->getJson('/api/v1/universities')->json('meta.facets');
        $this->assertEquals(['uk' => 2, 'germany' => 1], collect($facets['destinations'])->pluck('count', 'value')->all());
        $this->assertSame(1, collect($facets['types'])->firstWhere('value', 'private')['count']);
    }

    public function test_courses_inherit_destination_and_filter_by_it(): void
    {
        $tum = $this->university($this->de, ['name' => 'TUM']);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->postJson('/api/v1/admin/courses', [
            'university_id' => $tum->id, 'name' => 'MSc Robotics', 'study_level' => 'postgraduate', 'subject' => 'Engineering',
            'fee_period' => 'year', 'tuition_fee' => 3000, 'duration_months' => 24, 'status' => 'published',
        ])->assertCreated()->assertJsonPath('data.destination_id', $this->de->id);

        $this->getJson('/api/v1/courses?destination[]=germany&duration_max=24')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/courses?destination[]=uk')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/v1/courses/msc-robotics')->assertOk()->assertJsonPath('data.university.name', 'TUM');
        $this->get('/courses/msc-robotics')->assertOk()->assertSee('"@type":"Course"', false);
    }

    public function test_youtube_urls_are_parsed_and_validated(): void
    {
        $admin = $this->userWithRole('admin');
        $base = ['destination_id' => $this->uk->id, 'name' => 'Video Uni', 'type' => 'public', 'status' => 'published'];

        $this->actingAs($admin)->postJson('/api/v1/admin/universities', $base + ['video_url' => 'https://example.com/video'])
            ->assertStatus(422)->assertJsonValidationErrors(['video_url']);

        foreach (['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/shorts/dQw4w9WgXcQ'] as $i => $url) {
            $this->actingAs($admin)->postJson('/api/v1/admin/universities', ['name' => "Video Uni {$i}", 'video_url' => $url] + $base)
                ->assertCreated()->assertJsonPath('data.youtube_id', 'dQw4w9WgXcQ');
        }
    }

    public function test_cannot_delete_records_that_still_have_children(): void
    {
        $admin = $this->userWithRole('admin');
        $uni = $this->university($this->uk);
        $this->course($uni);

        $this->actingAs($admin)->deleteJson("/api/v1/admin/destinations/{$this->uk->id}")->assertStatus(422);
        $this->actingAs($admin)->deleteJson("/api/v1/admin/universities/{$uni->id}")->assertStatus(422);
        $this->actingAs($admin)->deleteJson("/api/v1/admin/destinations/{$this->de->id}")->assertOk();
    }

    public function test_enquiries_become_leads_with_the_right_source(): void
    {
        $uni = $this->university($this->uk, ['slug' => 'oxford']);
        $form = ['name' => 'Student', 'email' => 'Student@Example.com', 'phone' => '+971 50 123 4567'];

        $this->postJson('/api/v1/enquiries', $form + ['university' => 'oxford', 'intake' => 'September'])->assertCreated();
        $this->postJson('/api/v1/enquiries', $form + ['destination' => 'germany'])->assertCreated();
        $this->postJson('/api/v1/enquiries', $form + ['website' => 'http://spam'])->assertStatus(422);
        $this->postJson('/api/v1/enquiries', ['name' => 'x', 'email' => 'bad', 'phone' => 'abc'])->assertStatus(422)->assertJsonValidationErrors(['email', 'phone']);

        $leads = Lead::query()->orderBy('id')->get();
        $this->assertCount(2, $leads);
        $this->assertSame(LeadSource::UniversityEnquiry, $leads[0]->source);
        $this->assertSame($uni->id, $leads[0]->university_id);
        $this->assertSame($this->uk->id, $leads[0]->destination_id);
        $this->assertSame('September', $leads[0]->meta['intake']);
        $this->assertSame('student@example.com', $leads[0]->email);
        $this->assertSame(LeadSource::DestinationEnquiry, $leads[1]->source);
    }

    public function test_search_groups_results(): void
    {
        $this->course($this->university($this->uk, ['name' => 'Data University']), ['name' => 'MSc Data Science']);

        $this->getJson('/api/v1/search?q=data')->assertOk()
            ->assertJsonPath('data.universities.0.title', 'Data University')
            ->assertJsonPath('data.courses.0.title', 'MSc Data Science')
            ->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/search?q=a')->assertStatus(422);
    }

    public function test_listing_pages_keep_their_address_and_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $services = Page::query()->where('slug', 'services')->firstOrFail();

        $this->actingAs($admin)->putJson("/api/v1/admin/pages/{$services->id}", ['title' => 'Services', 'slug' => 'our-services', 'template' => 'listing', 'status' => 'published'])
            ->assertStatus(422)->assertJsonValidationErrors(['slug']);
        $this->actingAs($admin)->putJson("/api/v1/admin/pages/{$services->id}", ['title' => 'What We Do', 'slug' => 'services', 'template' => 'listing', 'status' => 'published'])
            ->assertOk();
        $this->actingAs($admin)->deleteJson("/api/v1/admin/pages/{$services->id}")->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/v1/admin/pages', ['title' => 'Fake', 'template' => 'listing', 'status' => 'published'])->assertStatus(422);
        $this->actingAs($admin)->postJson('/api/v1/admin/pages', ['title' => 'x', 'slug' => 'study-in-mars', 'template' => 'default', 'status' => 'published'])->assertStatus(422);
    }

    public function test_directory_permissions(): void
    {
        $counsellor = $this->userWithRole('counsellor');
        foreach (['destinations', 'universities', 'courses'] as $path) {
            $this->actingAs($counsellor)->getJson("/api/v1/admin/{$path}")->assertForbidden();
        }
    }
}
