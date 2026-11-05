<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum ContentStatus: string
{
    use EnumHelpers;

    case Published = 'published';
    case Draft = 'draft';
}
