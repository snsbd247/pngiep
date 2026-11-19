<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);
    }

    public function test_admin_saves_profile_message_and_highlights_with_sanitised_html(): void
    {
        $admin = $this->userWithRole('content-manager');

        $id = $this->actingAs($admin)->postJson('/api/v1/admin/team-members', [
            'name' => 'Jane Director', 'designation' => 'Managing Director', 'is_active' => true,
            'bio' => 'Leads the company.', 'highlights' => ['BSc, PNG University of Technology', '10 years in education'],
            'message_title' => 'Message from the Managing Director',
            'message' => '<p>Welcome<script>alert(1)</script></p>', 'profile' => '<h2>Career</h2><p>Many years.</p>',
            'seo' => ['title' => 'Jane Director profile'],
        ])->assertCreated()->json('data.id');

        $member = TeamMember::query()->findOrFail($id);
        $this->assertSame('jane-director', $member->slug);
        $this->assertStringNotContainsString('<script', (string) $member->message);

        $this->getJson('/api/v1/team/jane-director')->assertOk()
            ->assertJsonPath('data.name', 'Jane Director')
            ->assertJsonPath('data.message_title', 'Message from the Managing Director')
            ->assertJsonPath('data.highlights.1', '10 years in education')
            ->assertJsonPath('data.seo.title', 'Jane Director profile')
            ->assertJsonStructure(['data' => ['others']]);

        $this->get('/team/jane-director')->assertOk()->assertSee('"@type":"Person"', false)->assertSee('Jane Director profile', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/team/jane-director', false);
    }

    public function test_hidden_members_have_no_public_profile(): void
    {
        TeamMember::query()->create(['name' => 'Hidden Person', 'is_active' => false]);

        $this->getJson('/api/v1/team/hidden-person')->assertNotFound();
        $this->get('/team/hidden-person')->assertNotFound();
    }

    public function test_team_block_includes_profile_slugs(): void
    {
        TeamMember::query()->create(['name' => 'Card Person', 'is_active' => true]);

        $this->assertContains('card-person', array_column(app(\App\Services\ContentBlocks::class)->team(), 'slug'));
    }
}
