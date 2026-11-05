<?php

namespace App\Services;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;

/**
 * Captures every website enquiry as a CRM lead. Repeat contacts from the same
 * email reuse their open lead (new / contacted / interested / follow-up) instead
 * of creating duplicates; the latest details and interests are merged in.
 */
class LeadService
{
    public function __construct(private readonly TemplateMailer $mailer) {}

    public function capture(LeadSource $source, array $data, bool $notify = true): Lead
    {
        $email = strtolower(trim($data['email']));
        $open = [LeadStatus::New->value, LeadStatus::Contacted->value, LeadStatus::Interested->value, LeadStatus::FollowUp->value];

        $lead = Lead::query()->where('email', $email)->whereIn('status', $open)->latest('id')->first();
        $fields = array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'destination_id' => $data['destination_id'] ?? null,
            'university_id' => $data['university_id'] ?? null,
            'course_id' => $data['course_id'] ?? null,
            'study_level' => $data['study_level'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($lead) {
            $history = $lead->meta['history'] ?? [];
            $history[] = array_filter(['source' => $source->value, 'at' => now()->toIso8601String(), 'message' => $data['message'] ?? null]);
            $lead->fill($fields)->forceFill(['meta' => array_merge($lead->meta ?? [], ['history' => array_slice($history, -20)])])->save();

            return $lead;
        }

        $lead = Lead::query()->create($fields + [
            'email' => $email,
            'name' => $data['name'] ?? $email,
            'source' => $source,
            'status' => LeadStatus::New,
            'message' => $data['message'] ?? null,
            'meta' => $data['meta'] ?? null,
            'ip_address' => request()?->ip(),
        ]);

        if ($notify) {
            $this->mailer->toAdmin('admin_new_lead', [
                'name' => $lead->name, 'email' => $lead->email, 'phone' => $lead->phone ?? '—',
                'source' => $source->label(), 'message' => $lead->message ?? '—',
                'admin_url' => rtrim(config('app.frontend_url'), '/')."/admin/leads/{$lead->id}",
            ]);
        }

        return $lead;
    }
}
