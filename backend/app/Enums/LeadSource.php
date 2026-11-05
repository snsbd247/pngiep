<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum LeadSource: string
{
    use EnumHelpers;

    case ContactForm = 'contact_form';
    case Appointment = 'appointment';
    case ApplyNow = 'apply_now';
    case UniversityEnquiry = 'university_enquiry';
    case CourseEnquiry = 'course_enquiry';
    case DestinationEnquiry = 'destination_enquiry';
    case Newsletter = 'newsletter';
}
