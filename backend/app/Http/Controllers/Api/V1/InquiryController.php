<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DocumentType;
use App\Enums\LeadSource;
use App\Enums\StudyLevel;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ContactMessage;
use App\Models\Course;
use App\Models\Destination;
use App\Models\University;
use App\Services\AdminNotifier;
use App\Services\LeadService;
use App\Services\SettingService;
use App\Services\TemplateMailer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Contact form and "Apply Now" applications. Both create CRM leads and send
 * a confirmation to the student plus a notification to the office.
 */
class InquiryController extends Controller
{
    public const DOCUMENT_MIMES = 'pdf,jpg,jpeg,png,doc,docx';

    public const DOCUMENT_MAX_KB = 5120;

    public function __construct(private readonly LeadService $leads, private readonly TemplateMailer $mailer) {}

    /** Allowed inquiry types from Settings > Contact (one per line). Empty = free text. */
    private function inquiryTypes(): array
    {
        $raw = (string) app(SettingService::class)->get('contact_inquiry_types', '');

        return array_values(array_filter(array_map('trim', preg_split('/\R/', $raw))));
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'inquiry_type' => ['required', 'string', 'max:100', ...($this->inquiryTypes() ? [Rule::in($this->inquiryTypes())] : [])],
            'destination' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'agree' => ['accepted'],
            'website' => ['prohibited'],
        ], ['agree.accepted' => 'Please accept the terms to continue.', 'website.prohibited' => 'Your message could not be sent.']);

        $destinationId = isset($data['destination']) ? Destination::query()->where('slug', $data['destination'])->value('id') : null;
        $lead = $this->leads->capture(LeadSource::ContactForm, $data + ['destination_id' => $destinationId], notify: false);

        $message = ContactMessage::query()->create([
            'lead_id' => $lead->id,
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'inquiry_type' => $data['inquiry_type'],
            'destination_id' => $destinationId,
            'subject' => $data['subject'] ?? $data['inquiry_type'],
            'message' => $data['message'],
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 490, ''),
        ]);

        $vars = ['name' => $message->name, 'email' => $message->email, 'phone' => $message->phone, 'inquiry_type' => $message->inquiry_type,
            'message' => $message->message, 'admin_url' => rtrim(config('app.frontend_url'), '/')."/admin/messages/{$message->id}"];
        $this->mailer->send('contact_received', $message->email, $vars);
        $this->mailer->toAdmin('admin_new_contact', $vars);

        app(AdminNotifier::class)->notify('message', "New message from {$message->name}", $message->inquiry_type, "/admin/messages/{$message->id}");

        return ApiResponse::success(null, 'Thank you! Your message has been sent. We will get back to you shortly.', 201);
    }

    public function apply(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'destination' => ['nullable', 'string', 'max:100'],
            'university' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'string', 'max:190'],
            'study_level' => ['nullable', Rule::enum(StudyLevel::class)],
            'intake' => ['nullable', 'string', 'max:60'],
            'message' => ['nullable', 'string', 'max:5000'],
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*.type' => ['required', Rule::enum(DocumentType::class)],
            // Real MIME sniffing + extension + size; files go to the private "documents" disk.
            'documents.*.file' => ['required', 'file', 'mimes:'.self::DOCUMENT_MIMES, 'max:'.self::DOCUMENT_MAX_KB],
            'agree' => ['accepted'],
            'website' => ['prohibited'],
        ], [
            'documents.*.file.mimes' => 'Documents must be PDF, JPG, PNG or Word files.',
            'documents.*.file.max' => 'Each document must be 5 MB or smaller.',
            'agree.accepted' => 'Please accept the terms to continue.',
            'website.prohibited' => 'Your application could not be sent.',
        ]);

        $course = isset($data['course']) ? Course::query()->published()->where('slug', $data['course'])->first() : null;
        $university = $course?->university ?? (isset($data['university']) ? University::query()->published()->where('slug', $data['university'])->first() : null);
        $destination = $university?->destination ?? (isset($data['destination']) ? Destination::query()->published()->where('slug', $data['destination'])->first() : null);

        $stored = [];
        try {
            $application = DB::transaction(function () use ($data, $course, $university, $destination, $request, &$stored) {
                $lead = $this->leads->capture(LeadSource::ApplyNow, [
                    'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'message' => $data['message'] ?? null,
                    'destination_id' => $destination?->id, 'university_id' => $university?->id, 'course_id' => $course?->id, 'study_level' => $data['study_level'] ?? null,
                ], notify: false);

                $application = Application::query()->create([
                    'reference' => $this->reference(),
                    'lead_id' => $lead->id,
                    'destination_id' => $destination?->id,
                    'university_id' => $university?->id,
                    'course_id' => $course?->id,
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'phone' => $data['phone'],
                    'study_level' => $data['study_level'] ?? null,
                    'intake' => $data['intake'] ?? null,
                    'message' => $data['message'] ?? null,
                    'ip_address' => $request->ip(),
                ]);

                foreach ($request->file('documents', []) as $i => $doc) {
                    $file = $doc['file'];
                    // Random name, never the original: no guessing, no path tricks, no executable extensions.
                    $path = $file->storeAs($application->reference, Str::random(40).'.'.strtolower($file->guessExtension() ?: 'bin'), 'documents');
                    $stored[] = $path;
                    $application->documents()->create([
                        'type' => $data['documents'][$i]['type'],
                        'original_name' => Str::limit(basename($file->getClientOriginalName()), 250, ''),
                        'path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }

                return $application;
            });
        } catch (Throwable $e) {
            Storage::disk('documents')->delete($stored);
            throw $e;
        }

        $vars = ['name' => $application->name, 'email' => $application->email, 'phone' => $application->phone, 'reference' => $application->reference,
            'university' => $university?->name ?? '—', 'course' => $course?->name ?? '—', 'destination' => $destination?->name ?? '—',
            'documents' => (string) count($stored), 'admin_url' => rtrim(config('app.frontend_url'), '/')."/admin/applications/{$application->id}"];
        $this->mailer->send('application_received', $application->email, $vars);
        $this->mailer->toAdmin('admin_new_application', $vars);

        app(AdminNotifier::class)->notify('application', "New application from {$application->name}", $application->reference.($university ? " · {$university->name}" : ''), "/admin/applications/{$application->id}");

        return ApiResponse::success(['reference' => $application->reference], 'Your application has been submitted.', 201);
    }

    private function reference(): string
    {
        do {
            $reference = 'APP-'.strtoupper(Str::random(8));
        } while (Application::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }
}
