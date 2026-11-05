<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;
use App\Models\MenuItem;
use Closure;
use Illuminate\Validation\Rule;

class MenuItemRequest extends ContentRequest
{
    public function rules(): array
    {
        $id = $this->recordId();

        return [
            'menu_id' => ['required', 'integer', 'exists:menus,id'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('menu_items', 'id')->where('menu_id', $this->input('menu_id'))->whereNull('parent_id'),
                function (string $attribute, mixed $value, Closure $fail) use ($id) {
                    if ($id && (int) $value === $id) {
                        $fail('An item cannot be its own parent.');
                    }
                    if ($id && $value && MenuItem::query()->where('parent_id', $id)->exists()) {
                        $fail('This item has its own sub-items, so it cannot be moved under another item.');
                    }
                },
            ],
            'label' => ['required', 'string', 'max:100'],
            'link_type' => ['required', Rule::in(['internal', 'external', 'page'])],
            'url' => array_merge(['exclude_if:link_type,page'], $this->link($this->input('link_type') !== 'page')),
            'page_id' => ['exclude_unless:link_type,page', 'required', 'integer', 'exists:pages,id'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'icon' => $this->icon(),
            'is_active' => ['boolean'],
        ];
    }
}
