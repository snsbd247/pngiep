<?php

namespace App\Models;

use App\Models\Concerns\FlushesPublicCache;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogTag extends Model
{
    use FlushesPublicCache, HasSlug;

    protected $guarded = ['id'];

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_post_tags');
    }
}
