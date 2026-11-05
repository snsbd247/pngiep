<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Application;
use App\Models\Lead;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        $this->seed(EmailTemplateSeeder::class);
        Mail::fake();
        Storage::fake('documents');
    }

    private function apply(array $documents, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/api/v1/applications', $overrides + [
            'name' => 'Bob Smith', 'email' => 'bob@example.com', 'phone' => '0501234567', 'agree' => '1',
            'documents' => $documents,
        ], ['Accept' => 'application/json']);
    }

    public function test_application_with_documents_is_stored_privately(): void
    {
        $ref = $this->apply([
            ['type' => 'passport', 'file' => UploadedFile::fake()->create('passport.pdf', 200, 'application/pdf')],
            ['type' => 'transcript', 'file' => UploadedFile::fake()->image('transcript.jpg')],
        ])->assertCreated()->json('data.reference');

        $app = Application::query()->with('documents')->sole();
        $this->assertSame($ref, $app->reference);
        $this->assertCount(2, $app->documents);
        foreach ($app->documents as $doc) {
            Storage::disk('documents')->assertExists($doc->path);
            $this->assertStringStartsWith($ref.'/', $doc->path);
            $this->assertStringNotContainsString('passport', $doc->path, 'Stored names are random');
        }
        $this->assertSame(1, Lead::query()->count());
        Mail::assertQueued(TemplatedMail::class, 2);

        // Nothing about the file leaks into the public response.
        $this->assertStringNotContainsString('documents', json_encode($this->apply([])->json() ?? []));
    }

    public function test_dangerous_or_oversized_files_are_rejected(): void
    {
        $this->apply([['type' => 'passport', 'file' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php')]])
            ->assertJsonValidationErrors('documents.0.file');
        $this->apply([['type' => 'passport', 'file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')]])
            ->assertJsonValidationErrors('documents.0.file');
        $this->apply([['type' => 'malware', 'file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')]])
            ->assertJsonValidationErrors('documents.0.type');
        $this->apply([], ['website' => 'bot'])->assertJsonValidationErrors('website');

        $this->assertSame(0, Application::query()->count());
        $this->assertSame([], Storage::disk('documents')->allFiles());
    }

    public function test_documents_download_only_for_authorised_staff(): void
    {
        $this->apply([['type' => 'passport', 'file' => UploadedFile::fake()->create('passport.pdf', 20, 'application/pdf')]])->assertCreated();
        $app = Application::query()->with('documents')->sole();
        $doc = $app->documents->first();
        $url = "/api/v1/admin/applications/{$app->id}/documents/{$doc->id}";

        $this->getJson($url)->assertUnauthorized();
        $this->actingAs($this->userWithRole('counsellor'))->getJson($url)->assertForbidden();
        $this->actingAs($this->userWithRole('content-manager'))->getJson($url)->assertForbidden();

        $admin = $this->userWithRole('admin');
        $res = $this->actingAs($admin)->get($url)->assertOk();
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));
        $this->assertStringContainsString('passport.pdf', $res->headers->get('Content-Disposition'));

        // Document IDs must belong to the application in the URL.
        $this->actingAs($admin)->getJson("/api/v1/admin/applications/999/documents/{$doc->id}")->assertNotFound();

        $this->actingAs($admin)->getJson("/api/v1/admin/applications/{$app->id}")->assertOk()
            ->assertJsonMissingPath('data.documents.0.path');
    }
}
