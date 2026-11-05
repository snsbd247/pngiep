<?php

namespace App\Http\Controllers\Api\V1\Admin\Cms;

use App\Http\Controllers\Api\V1\Admin\CrudController;
use App\Http\Requests\Admin\Cms\MenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\ApiResponse;
use App\Support\PublicCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Menu items CRUD (via CrudController) plus menu-level endpoints.
 * Menus themselves are fixed theme locations (header, footer columns …).
 */
class MenuController extends CrudController
{
    protected string $model = MenuItem::class;

    protected string $request = MenuItemRequest::class;

    protected array $searchable = ['label'];

    protected array $filters = ['menu_id' => 'menu_id'];

    /** All menus with their full item trees (including hidden items). */
    public function menus(): JsonResponse
    {
        $menus = Menu::query()->orderBy('id')->with(['items' => fn ($q) => $q->with('page:id,title,slug')->orderBy('sort_order')])->get();

        return ApiResponse::success($menus->map(fn (Menu $menu) => [
            'id' => $menu->id,
            'name' => $menu->name,
            'location' => $menu->location,
            'is_active' => $menu->is_active,
            'items' => $menu->items->whereNull('parent_id')->values()->map(fn (MenuItem $item) => $item->toArray() + [
                'children' => $menu->items->where('parent_id', $item->id)->values()->toArray(),
            ]),
        ]));
    }

    public function updateMenu(Request $request, Menu $menu): JsonResponse
    {
        $menu->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ]));

        return ApiResponse::success($menu, 'Menu saved.');
    }

    /**
     * Drag & drop result for one menu: { items: [{ id, parent_id }, …] } in display order.
     * Only one level of nesting is allowed (dropdowns).
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items' => ['required', 'array', 'max:500'],
            'items.*.id' => ['required', 'integer'],
            'items.*.parent_id' => ['nullable', 'integer'],
        ]);

        $ids = collect($data['items'])->pluck('id');
        abort_if(MenuItem::query()->whereIn('id', $ids)->where('menu_id', '!=', $data['menu_id'])->exists(), 422, 'Items belong to another menu.');

        $parents = collect($data['items'])->pluck('parent_id')->filter()->unique();
        $nested = collect($data['items'])->filter(fn ($i) => $i['parent_id'] && $parents->contains($i['id']));
        abort_if($nested->isNotEmpty(), 422, 'Menus support one level of sub-items.');

        DB::transaction(function () use ($data, $ids) {
            foreach ($data['items'] as $position => $item) {
                $parent = $item['parent_id'] && $ids->contains($item['parent_id']) ? $item['parent_id'] : null;
                MenuItem::query()->whereKey($item['id'])->update(['sort_order' => $position, 'parent_id' => $parent]);
            }
        });
        PublicCache::flush();

        return ApiResponse::success(null, 'Menu order saved.');
    }
}
