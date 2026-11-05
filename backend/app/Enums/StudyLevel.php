<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;

enum StudyLevel: string
{
    use EnumHelpers;

    case Foundation = 'foundation';
    case Diploma = 'diploma';
    case Undergraduate = 'undergraduate';
    case Postgraduate = 'postgraduate';
    case Doctorate = 'doctorate';
    case Language = 'language';
}
