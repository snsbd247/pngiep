<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\PublicCache;
use Illuminate\Support\Collection;

class MenuService
{
    /** Active menus keyed by location, each a nested tree of active items. */
    public function tree(): array
    {
        return PublicCache::remember('menus', function () {
            $menus = Menu::query()->where('is_active', true)
                ->with(['items' => fn ($q) => $q->active()->with('page:id,slug')])
                ->get();

            return $menus->mapWithKeys(fn (Menu $menu) => [
                $menu->location => [
                    'name' => $menu->name,
                    'items' => $this->buildTree($menu->items),
                ],
            ])->all();
        });
    }

    public function flush(): void
    {
        PublicCache::flush();
    }

    private function buildTree(Collection $items, ?int $parentId = null): array
    {
        return $items->where('parent_id', $parentId)
            ->sortBy('sort_order')
            ->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $this->resolveUrl($item),
                'is_external' => $item->link_type === 'external',
                'target' => $item->target,
                'icon' => $item->icon,
                'children' => $this->buildTree($items, $item->id),
            ])
            ->values()
            ->all();
    }

    private function resolveUrl(MenuItem $item): string
    {
        if ($item->link_type === 'page' && $item->page) {
            return '/'.$item->page->slug;
        }

        return $item->url ?: '#';
    }
}
