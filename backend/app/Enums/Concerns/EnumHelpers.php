<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait EnumHelpers
{
    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return Str::headline($this->value);
    }

    /** Options for admin select inputs: [{value, label}]. */
    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
