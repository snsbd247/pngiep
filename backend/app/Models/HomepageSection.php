<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use FlushesPublicCache, HasActiveFlag, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'items_limit' => 'integer',
        ];
    }
}
