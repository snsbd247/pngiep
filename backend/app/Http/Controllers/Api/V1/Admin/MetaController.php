<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AppointmentStatus;
use App\Enums\ContactStatus;
use App\Enums\ContentStatus;
use App\Enums\DocumentType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\StudyLevel;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Select-box options for admin forms, so status lists live in one place (the PHP enums).
 */
class MetaController extends Controller
{
    /** Section schemas from config/sections.php: the admin builds section forms from these. */
    public function sections(): JsonResponse
    {
        return ApiResponse::success([
            'page_types' => config('sections.page_types'),
            'homepage' => config('sections.homepage'),
        ]);
    }

    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'content_statuses' => ContentStatus::options(),
            'appointment_statuses' => AppointmentStatus::options(),
            'application_statuses' => ApplicationStatus::options(),
            'lead_statuses' => LeadStatus::options(),
            'lead_sources' => LeadSource::options(),
            'contact_statuses' => ContactStatus::options(),
            'study_levels' => StudyLevel::options(),
            'document_types' => DocumentType::options(),
        ]);
    }
}
