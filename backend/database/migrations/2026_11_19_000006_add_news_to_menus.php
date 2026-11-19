<?php

use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Database\Migrations\Migration;

/** Adds "News" right after "Blog" in the header and footer quick-links menus (skipped if already there). */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['header', 'footer_quick'] as $location) {
            $menu = Menu::query()->where('location', $location)->first();
            if (! $menu || $menu->items()->where('url', '/news')->exists()) {
                continue;
            }
            $blog = $menu->items()->whereNull('parent_id')->where('url', '/blog')->first();
            $position = $blog ? $blog->sort_order + 1 : (int) $menu->items()->whereNull('parent_id')->max('sort_order') + 1;
            $menu->items()->whereNull('parent_id')->where('sort_order', '>=', $position)->increment('sort_order');
            $menu->items()->create(['label' => 'News', 'link_type' => 'internal', 'url' => '/news', 'sort_order' => $position]);
        }
        app(MenuService::class)->flush();
    }

    public function down(): void
    {
        Menu::query()->whereIn('location', ['header', 'footer_quick'])->get()
            ->each(fn (Menu $menu) => $menu->items()->where('url', '/news')->where('label', 'News')->delete());
        app(MenuService::class)->flush();
    }
};
