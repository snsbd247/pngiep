<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use FlushesPublicCache, HasPublishing, LogsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'rating' => 'integer',
        ];
    }
}
