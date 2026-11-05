<?php

namespace App\Http\Requests\Admin\Cms;

use App\Http\Requests\Admin\ContentRequest;
use App\Models\Page;
use App\Support\SectionSchema;
use Illuminate\Validation\Rule;

class PageRequest extends ContentRequest
{
    public const TEMPLATES = ['default', 'about'];

    /** URL segments owned by built-in pages; a CMS page may not take them over. */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'storage', 'spa', 'sanctum', 'destinations', 'universities', 'courses', 'services',
        'success-stories', 'gallery', 'video-gallery', 'blog', 'contact', 'book-appointment', 'search', 'apply', 'appointment', 'sitemap.xml', 'robots.txt',
    ];

    public function rules(): array
    {
        // Built-in list pages (services, universities …) keep their address and template.
        $existing = $this->recordId() ? Page::query()->find($this->recordId()) : null;
        $isListing = $existing?->template === 'listing';

        $rules = [
            'title' => ['required', 'string', 'max:190'],
            'slug' => $isListing
                ? ['nullable', Rule::in([$existing->slug])]
                : [...$this->slug('pages'), Rule::notIn(self::RESERVED_SLUGS), 'not_regex:/^study-in-/'],
            'template' => ['required', Rule::in($isListing ? ['listing'] : self::TEMPLATES)],
            'page_category_id' => ['nullable', 'integer', Rule::exists('terms', 'id')->where('taxonomy', 'page_category')],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'banner_image' => $this->image(),
            'content' => ['nullable', 'string', 'max:200000'],
            'status' => $this->status(),
            ...$this->seo(),
        ];

        return array_merge($rules, SectionSchema::listRules((array) $this->input('sections', [])));
    }

    public function messages(): array
    {
        return [
            'slug.not_in' => 'This address is used by a built-in page. Please choose another.',
            'slug.not_regex' => 'Addresses starting with "study-in-" are reserved for destinations.',
        ];
    }
}
