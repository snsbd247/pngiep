<?php

use App\Models\Partner;
use App\Models\University;
use App\Services\MenuService;
use Illuminate\Database\Migrations\Migration;

/**
 * Universities are now shown in the partner logo strips straight from Universities, so the
 * "University" partners that duplicate a university (same name) are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $names = University::query()->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->all();

        Partner::query()->get()
            ->filter(fn (Partner $p) => strcasecmp(trim((string) $p->category), 'university') === 0 && in_array(mb_strtolower(trim($p->name)), $names, true))
            ->each->delete();

        app(MenuService::class)->flush(); // clears the public cache (homepage / page blocks)
    }

    public function down(): void
    {
        // Restore from the pre-deploy backup if needed.
    }
};
