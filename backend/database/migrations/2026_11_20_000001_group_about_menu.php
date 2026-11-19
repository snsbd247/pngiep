<?php

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\MenuService;
use Illuminate\Database\Migrations\Migration;

/** Header menu: "About" dropdown holding About Us, Services and Gallery (skipped if already grouped). */
return new class extends Migration
{
    public function up(): void
    {
        $menu = Menu::query()->where('location', 'header')->first();
        if (! $menu) {
            return;
        }
        $top = $menu->items()->whereNull('parent_id')->orderBy('sort_order')->get();
        if ($top->contains(fn (MenuItem $i) => $i->label === 'About' && $menu->items()->where('parent_id', $i->id)->exists())) {
            return;
        }

        $aboutPage = Page::query()->where('slug', 'about')->value('id');
        $children = array_values(array_filter([
            $top->first(fn (MenuItem $i) => ($aboutPage && $i->page_id === $aboutPage) || $i->label === 'About Us'),
            $top->firstWhere('url', '/services'),
            $top->firstWhere('url', '/gallery'),
        ]));
        if (! $children) {
            return;
        }

        $parent = $menu->items()->create([
            'label' => 'About',
            'link_type' => $aboutPage ? 'page' : 'internal',
            'page_id' => $aboutPage,
            'url' => $aboutPage ? null : '/about',
            'sort_order' => $children[0]->sort_order,
        ]);
        foreach ($children as $i => $child) {
            $child->update(['parent_id' => $parent->id, 'sort_order' => $i]);
        }

        // Renumber the top level: Home, About, Destinations, …
        $menu->items()->whereNull('parent_id')->orderBy('sort_order')->orderByRaw('id = ? desc', [$parent->id])->get()
            ->each(fn (MenuItem $item, int $i) => $item->update(['sort_order' => $i]));

        app(MenuService::class)->flush();
    }

    public function down(): void
    {
        $menu = Menu::query()->where('location', 'header')->first();
        $parent = $menu?->items()->whereNull('parent_id')->where('label', 'About')->first();
        if (! $parent) {
            return;
        }
        // Put the children back at the top level, where the "About" item was.
        $children = $menu->items()->where('parent_id', $parent->id)->orderBy('sort_order')->get();
        $order = $menu->items()->whereNull('parent_id')->orderBy('sort_order')->get()
            ->flatMap(fn (MenuItem $item) => $item->is($parent) ? $children : [$item]);
        $order->each(fn (MenuItem $item, int $i) => $item->update(['parent_id' => null, 'sort_order' => $i]));
        $parent->delete();
        app(MenuService::class)->flush();
    }
};
