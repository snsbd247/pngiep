<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasSeo;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

/** A director / team member: card on the About page and a profile page at /team/{slug}. */
class TeamMember extends Model
{
    use FlushesPublicCache, HasActiveFlag, HasSeo, HasSlug, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'highlights' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
