<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ContactStatus: string
{
    use EnumHelpers;

    case New = 'new';
    case Read = 'read';
    case Replied = 'replied';
    case Archived = 'archived';
}
