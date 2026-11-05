<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ApplicationStatus: string
{
    use EnumHelpers;

    case New = 'new';
    case Contacted = 'contacted';
    case DocumentsPending = 'documents_pending';
    case UnderReview = 'under_review';
    case Submitted = 'submitted';
    case Processing = 'processing';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
