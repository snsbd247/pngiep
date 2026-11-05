<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadSource;
use App\Enums\StudyLevel;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Destination;
use App\Models\Lead;
use App\Services\AdminNotifier;
use App\Models\University;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/enquiries: consultation / "interested in studying here" forms on
 * destination, university and course pages. Each enquiry becomes a Lead
 * (managed in the CRM, Phase 5).
 */
class EnquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'study_level' => ['nullable', Rule::enum(StudyLevel::class)],
            'intake' => ['nullable', 'string', 'max:60'],
            'interest' => ['nullable', 'string', 'max:190'],
            'message' => ['nullable', 'string', 'max:2000'],
            'destination' => ['nullable', 'string', 'max:190'],
            'university' => ['nullable', 'string', 'max:190'],
            'course' => ['nullable', 'string', 'max:190'],
            'page_url' => ['nullable', 'string', 'max:500'],
            // Honeypot: real visitors never see or fill this field.
            'website' => ['prohibited'],
        ], ['website.prohibited' => 'Your enquiry could not be sent.']);

        $course = isset($data['course']) ? Course::query()->published()->where('slug', $data['course'])->first() : null;
        $university = $course?->university ?? (isset($data['university']) ? University::query()->published()->where('slug', $data['university'])->first() : null);
        $destination = $university?->destination ?? (isset($data['destination']) ? Destination::query()->published()->where('slug', $data['destination'])->first() : null);

        $source = match (true) {
            $course !== null => LeadSource::CourseEnquiry,
            $university !== null => LeadSource::UniversityEnquiry,
            default => LeadSource::DestinationEnquiry,
        };

        $lead = Lead::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'source' => $source,
            'destination_id' => $destination?->id,
            'university_id' => $university?->id,
            'course_id' => $course?->id,
            'study_level' => $data['study_level'] ?? null,
            'message' => $data['message'] ?? null,
            'meta' => array_filter(['intake' => $data['intake'] ?? null, 'interest' => $data['interest'] ?? null, 'page_url' => $data['page_url'] ?? null]),
            'ip_address' => $request->ip(),
        ]);

        app(AdminNotifier::class)->notify('enquiry', "New enquiry from {$lead->name}", $course?->name ?? $university?->name ?? $destination?->name, "/admin/leads/{$lead->id}");

        return ApiResponse::success(null, 'Thank you! Our counsellor will contact you shortly.', 201);
    }
}
