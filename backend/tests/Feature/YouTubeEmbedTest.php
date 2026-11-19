<?php

namespace Tests\Feature;

use App\Models\SuccessStory;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouTubeEmbedTest extends TestCase
{
    use RefreshDatabase;

    private const EMBED = '<div data-youtube-video=""><iframe width="640" height="360" allowfullscreen="true" src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0"></iframe></div>';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(ContentSeeder::class);
    }

    public function test_rich_text_keeps_youtube_embeds_and_drops_other_iframes(): void
    {
        $admin = $this->userWithRole('content-manager');

        $html = '<p>Watch:</p>'.self::EMBED
            .'<iframe src="https://evil.example/embed/dQw4w9WgXcQ"></iframe>'
            .'<iframe src="https://www.youtube.com.evil.example/embed/dQw4w9WgXcQ"></iframe>'
            .'<iframe srcdoc="<script>alert(1)</script>"></iframe>';

        $id = $this->actingAs($admin)->postJson('/api/v1/admin/success-stories', [
            'student_name' => 'Video Student', 'status' => 'published', 'story' => $html,
        ])->assertCreated()->json('data.id');

        $story = (string) SuccessStory::query()->findOrFail($id)->story;

        $this->assertStringContainsString('<div data-youtube-video="">', $story);
        $this->assertStringContainsString('src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0"', $story);
        $this->assertSame(1, substr_count($story, '<iframe'), 'only the YouTube iframe survives');
        $this->assertStringNotContainsString('evil', $story);
        $this->assertStringNotContainsString('srcdoc', $story);
    }

    public function test_visitor_story_can_include_a_youtube_link_saved_as_draft(): void
    {
        $this->postJson('/api/v1/success-stories', [
            'name' => 'Visitor', 'email' => 'visitor@example.com',
            'story' => 'I got my visa and started my degree this year.',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertCreated();

        $story = SuccessStory::query()->where('submitter_email', 'visitor@example.com')->firstOrFail();
        $this->assertSame('dQw4w9WgXcQ', $story->youtube_id);
        $this->assertSame('draft', $story->status->value ?? $story->status);

        $this->postJson('/api/v1/success-stories', [
            'name' => 'Visitor', 'email' => 'visitor2@example.com',
            'story' => 'I got my visa and started my degree this year.',
            'video_url' => 'https://vimeo.com/123456',
        ])->assertUnprocessable()->assertJsonValidationErrors('video_url');
    }
}
