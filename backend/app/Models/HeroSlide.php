<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    use FlushesPublicCache, HasActiveFlag, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
