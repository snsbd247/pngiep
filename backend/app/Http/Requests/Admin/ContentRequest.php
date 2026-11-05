<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentStatus;
use App\Support\SafeUrl;
use App\Support\YouTube;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Base for admin content forms: shared rule helpers so every module validates
 * links, images, slugs, status and SEO the same way. Authorisation is enforced
 * by the route's `can:` middleware.
 */
abstract class ContentRequest extends FormRequest
{
    /** Route parameter id when updating, null when creating. */
    protected function recordId(): ?int
    {
        $id = $this->route('id');

        return $id === null ? null : (int) $id;
    }

    protected function link(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:500', new SafeUrl];
    }

    protected function image(): array
    {
        return $this->link();
    }

    protected function icon(): array
    {
        return ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/'];
    }

    protected function slug(string $table): array
    {
        return ['nullable', 'string', 'max:190', 'alpha_dash', Rule::unique($table, 'slug')->ignore($this->recordId())];
    }

    /** Optional YouTube URL (watch, youtu.be, shorts, embed). */
    protected function youtube(): array
    {
        return ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
            if ($value && ! YouTube::isValid($value)) {
                $fail('Enter a valid YouTube video URL.');
            }
        }];
    }

    protected function status(): array
    {
        return ['required', Rule::enum(ContentStatus::class)];
    }

    /** Rules for the optional `seo` object (title, description, OG, robots, canonical). */
    protected function seo(): array
    {
        return [
            'seo' => ['nullable', 'array'],
            'seo.title' => ['nullable', 'string', 'max:190'],
            'seo.description' => ['nullable', 'string', 'max:500'],
            'seo.keywords' => ['nullable', 'string', 'max:500'],
            'seo.canonical_url' => ['nullable', 'url', 'max:255'],
            'seo.og_title' => ['nullable', 'string', 'max:190'],
            'seo.og_description' => ['nullable', 'string', 'max:500'],
            'seo.og_image' => $this->image(),
            'seo.robots' => ['nullable', 'in:index\,follow,noindex\,follow,index\,nofollow,noindex\,nofollow'],
        ];
    }
}
