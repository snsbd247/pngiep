<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Default email templates (editable in Admin > Email Templates). Existing templates
 * are never overwritten. {{placeholders}} are listed per template for the editor.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $details = '<table cellpadding="6" style="border-collapse:collapse;margin:16px 0;font-size:14px;">'
            .'<tr><td style="color:#64748b;">Reference</td><td><strong>{{reference}}</strong></td></tr>'
            .'<tr><td style="color:#64748b;">Date</td><td>{{date}}</td></tr>'
            .'<tr><td style="color:#64748b;">Time</td><td>{{time}} ({{timezone}})</td></tr>'
            .'<tr><td style="color:#64748b;">Counsellor</td><td>{{counsellor}}</td></tr>'
            .'<tr><td style="color:#64748b;">Service</td><td>{{service}}</td></tr>'
            .'<tr><td style="color:#64748b;">Meeting</td><td>{{mode}}</td></tr></table>';
        $manage = '<p><a href="{{manage_url}}" style="display:inline-block;background:{{brand_color}};color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Manage my appointment</a></p>';
        $appointmentVars = ['name', 'reference', 'date', 'time', 'timezone', 'counsellor', 'service', 'mode', 'manage_url', 'site_name'];

        $templates = [
            'appointment_received' => ['Appointment request received (student)', 'We received your appointment request – {{reference}}',
                "<p>Dear {{name}},</p><p>Thank you for booking a consultation with {{site_name}}. We have received your request and will confirm it shortly.</p>{$details}{$manage}<p>Best regards,<br>{{site_name}}</p>", $appointmentVars],
            'appointment_confirmed' => ['Appointment confirmed (student)', 'Your appointment is confirmed – {{date}}',
                "<p>Dear {{name}},</p><p>Your consultation is confirmed. We look forward to speaking with you.</p>{$details}{$manage}<p>Best regards,<br>{{site_name}}</p>", $appointmentVars],
            'appointment_rescheduled' => ['Appointment rescheduled (student)', 'Your appointment has been rescheduled – {{reference}}',
                "<p>Dear {{name}},</p><p>Your appointment previously on {{old_date}} at {{old_time}} has been moved. Here are the new details:</p>{$details}{$manage}<p>Best regards,<br>{{site_name}}</p>", [...$appointmentVars, 'old_date', 'old_time']],
            'appointment_cancelled' => ['Appointment cancelled (student)', 'Your appointment has been cancelled – {{reference}}',
                '<p>Dear {{name}},</p><p>Your appointment on {{date}} at {{time}} has been cancelled.</p><p>{{reason}}</p><p>You can book a new time at any time on our website: <a href="{{site_url}}/book-appointment">{{site_url}}/book-appointment</a></p><p>Best regards,<br>{{site_name}}</p>', [...$appointmentVars, 'reason', 'site_url']],
            'admin_new_appointment' => ['New appointment (office)', 'New appointment: {{name}} – {{date}} {{time}}',
                "<p>A new appointment has been booked.</p>{$details}<p><strong>{{name}}</strong><br>{{email}}<br>{{phone}}</p><p><a href=\"{{admin_url}}\">Open in admin</a></p>", [...$appointmentVars, 'email', 'phone', 'admin_url', 'destination']],
            'admin_appointment_changed' => ['Appointment changed by student (office)', 'Appointment {{change}}: {{name}} – {{reference}}',
                "<p>An appointment was {{change}}.</p>{$details}<p><a href=\"{{admin_url}}\">Open in admin</a></p>", [...$appointmentVars, 'change', 'admin_url']],
            'contact_received' => ['Contact form auto-reply (student)', 'We received your message',
                '<p>Dear {{name}},</p><p>Thank you for contacting {{site_name}}. Our team will get back to you shortly.</p><p style="color:#64748b;">Your message:<br>{{message}}</p><p>Best regards,<br>{{site_name}}<br>{{contact_phone}}</p>', ['name', 'message', 'inquiry_type', 'site_name', 'contact_phone']],
            'admin_new_contact' => ['New contact message (office)', 'New enquiry: {{inquiry_type}} from {{name}}',
                '<p><strong>{{name}}</strong><br>{{email}}<br>{{phone}}</p><p><strong>{{inquiry_type}}</strong></p><p>{{message}}</p><p><a href="{{admin_url}}">Open in admin</a></p>', ['name', 'email', 'phone', 'inquiry_type', 'message', 'admin_url']],
            'contact_reply' => ['Reply to a contact message (student)', 'Re: your message to {{site_name}}',
                '<p>Dear {{name}},</p><p>{{reply}}</p><p>Best regards,<br>{{site_name}}</p><hr><p style="color:#64748b;font-size:13px;">Your original message:<br>{{original_message}}</p>', ['name', 'reply', 'original_message', 'site_name']],
            'application_received' => ['Application received (student)', 'Application received – {{reference}}',
                '<p>Dear {{name}},</p><p>Thank you for applying with {{site_name}}. Your application reference is <strong>{{reference}}</strong>.</p><p>University: {{university}}<br>Course: {{course}}</p><p>A counsellor will review your documents and contact you soon.</p><p>Best regards,<br>{{site_name}}</p>', ['name', 'reference', 'university', 'course', 'destination', 'site_name']],
            'admin_new_application' => ['New application (office)', 'New application {{reference}} from {{name}}',
                '<p><strong>{{name}}</strong><br>{{email}}<br>{{phone}}</p><p>Destination: {{destination}}<br>University: {{university}}<br>Course: {{course}}<br>Documents: {{documents}}</p><p><a href="{{admin_url}}">Open in admin</a></p>', ['name', 'email', 'phone', 'reference', 'destination', 'university', 'course', 'documents', 'admin_url']],
            'admin_new_lead' => ['New enquiry / lead (office)', 'New lead: {{name}} ({{source}})',
                '<p>A new enquiry arrived from the website.</p><p><strong>{{name}}</strong><br>{{email}}<br>{{phone}}</p><p>Source: {{source}}</p><p>{{message}}</p><p><a href="{{admin_url}}">Open in admin</a></p>', ['name', 'email', 'phone', 'source', 'message', 'admin_url']],
        ];

        foreach ($templates as $key => [$name, $subject, $body, $variables]) {
            EmailTemplate::query()->firstOrCreate(['key' => $key], compact('name', 'subject', 'body', 'variables') + ['is_active' => true]);
        }
    }
}
