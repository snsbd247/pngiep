<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum LeadStatus: string
{
    use EnumHelpers;

    case New = 'new';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case FollowUp = 'follow_up';
    case Converted = 'converted';
    case Closed = 'closed';
}
