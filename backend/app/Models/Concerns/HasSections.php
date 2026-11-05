<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * For models with a "{model}_sections" table that drives flexible detail-page sections.
 * The using model must define $sectionModel.
 */
trait HasSections
{
    public function sections(): HasMany
    {
        return $this->hasMany($this->sectionModel)->orderBy('sort_order')->orderBy('id');
    }

    public function activeSections(): HasMany
    {
        return $this->sections()->where('is_active', true);
    }
}
