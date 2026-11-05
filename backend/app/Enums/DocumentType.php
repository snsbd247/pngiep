<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum DocumentType: string
{
    use EnumHelpers;

    case Passport = 'passport';
    case AcademicCertificate = 'academic_certificate';
    case Transcript = 'transcript';
    case Ielts = 'ielts';
    case Cv = 'cv';
    case Other = 'other';
}
