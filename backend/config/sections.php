<?php

/*
|--------------------------------------------------------------------------
| Section schemas
|--------------------------------------------------------------------------
| Single source of truth for flexible content blocks.
| - The API validates and sanitises `data` from these definitions (App\Support\SectionSchema).
| - The admin panel renders its forms from them (GET /api/v1/admin/meta/sections).
|
| Field types: text, textarea, html, image, images, url, icon, number, select, toggle, repeater.
| Every section also has the common columns eyebrow / title / subtitle.
| Titles support *highlight* markup: words wrapped in asterisks render in the brand colour.
*/

$button = fn (string $prefix, string $label) => [
    ['name' => "{$prefix}_text", 'label' => "{$label} text", 'type' => 'text'],
    ['name' => "{$prefix}_url", 'label' => "{$label} link", 'type' => 'url'],
];

$featureItems = ['name' => 'items', 'label' => 'Items', 'type' => 'repeater', 'max' => 12, 'fields' => [
    ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
]];

$width = ['name' => 'width', 'label' => 'Width', 'type' => 'select', 'help' => 'Two consecutive half-width sections sit side by side.', 'options' => [
    ['value' => 'full', 'label' => 'Full width'],
    ['value' => 'half', 'label' => 'Half width'],
]];

return [
    // Block types available on CMS pages and on destination / university / service detail pages.
    'page_types' => [
        'story_videos' => ['label' => 'Video testimonials (automatic)', 'note' => 'Shows published success stories that have a YouTube video.', 'fields' => [
            ['name' => 'limit', 'label' => 'How many to show', 'type' => 'number'],
            ...$button('link', 'Link'),
        ]],
        'offices' => ['label' => 'Offices & map', 'note' => 'Map from Settings > Contact > Google Maps embed URL, plus office cards.', 'fields' => [
            ['name' => 'items', 'label' => 'Offices', 'type' => 'repeater', 'max' => 12, 'fields' => [
                ['name' => 'name', 'label' => 'Office name', 'type' => 'text', 'required' => true],
                ['name' => 'address', 'label' => 'Address', 'type' => 'textarea'],
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
                ['name' => 'directions_url', 'label' => 'Directions link (Google Maps)', 'type' => 'url'],
            ]],
        ]],
        'sidebar_card' => ['label' => 'Sidebar promo card (list pages)', 'note' => 'Shown in the right-hand column of the blog, gallery and success-stories pages. Title and subtitle are the card text.', 'fields' => [
            ['name' => 'kind', 'label' => 'Card type', 'type' => 'select', 'options' => [
                ['value' => 'link', 'label' => 'Text + button'],
                ['value' => 'newsletter', 'label' => 'Newsletter sign-up'],
                ['value' => 'list', 'label' => 'List of points (icon + title + text)'],
            ]],
            ['name' => 'items', 'label' => 'Points (for "List" cards)', 'type' => 'repeater', 'max' => 8, 'fields' => [
                ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['name' => 'text', 'label' => 'Text', 'type' => 'text'],
            ]],
            ...$button('button', 'Button'),
            ['name' => 'image', 'label' => 'Background / side photo', 'type' => 'image'],
            ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
        ]],
        'listing' => ['label' => 'Main list (on list pages)', 'note' => 'Marks where the services / universities / courses / destinations list appears. Eyebrow, title and subtitle become its heading.', 'fields' => []],
        'rich_text' => ['label' => 'Rich text', 'fields' => [
            ['name' => 'body', 'label' => 'Content', 'type' => 'html'],
            $width,
        ]],
        'text_images' => ['label' => 'Text + photo row', 'fields' => [
            ['name' => 'body', 'label' => 'Content', 'type' => 'html'],
            ['name' => 'images', 'label' => 'Photos (up to 4)', 'type' => 'images', 'max' => 4],
        ]],
        'list' => ['label' => 'Bullet list', 'fields' => [
            ['name' => 'items', 'label' => 'Points', 'type' => 'repeater', 'max' => 20, 'fields' => [
                ['name' => 'text', 'label' => 'Text', 'type' => 'text', 'required' => true],
            ]],
            $width,
        ]],
        'table' => ['label' => 'Two-column table', 'fields' => [
            ['name' => 'column_1', 'label' => 'Left column heading', 'type' => 'text'],
            ['name' => 'column_2', 'label' => 'Right column heading', 'type' => 'text'],
            ['name' => 'rows', 'label' => 'Rows', 'type' => 'repeater', 'max' => 30, 'fields' => [
                ['name' => 'label', 'label' => 'Left', 'type' => 'text', 'required' => true],
                ['name' => 'value', 'label' => 'Right', 'type' => 'text'],
            ]],
            $width,
        ]],
        'gallery' => ['label' => 'Photo gallery', 'fields' => [
            ['name' => 'images', 'label' => 'Photos', 'type' => 'images', 'max' => 12],
            ['name' => 'link_text', 'label' => 'Link text', 'type' => 'text'],
            ['name' => 'link_url', 'label' => 'Link', 'type' => 'url'],
        ]],
        'related_universities' => ['label' => 'Universities (automatic)', 'note' => 'Destination: its universities. University: others in the same country.', 'fields' => [
            ['name' => 'limit', 'label' => 'How many to show', 'type' => 'number'],
            ['name' => 'link_text', 'label' => 'Link text', 'type' => 'text'],
            ['name' => 'link_url', 'label' => 'Link', 'type' => 'url'],
        ]],
        'related_courses' => ['label' => 'Courses (automatic, with level tabs)', 'note' => 'Shows published courses of this destination or university.', 'fields' => [
            ['name' => 'limit', 'label' => 'How many to show', 'type' => 'number'],
            ['name' => 'link_text', 'label' => 'Link text', 'type' => 'text'],
            ['name' => 'link_url', 'label' => 'Link', 'type' => 'url'],
        ]],
        'intro' => ['label' => 'Introduction (text + photos)', 'fields' => [
            ['name' => 'body', 'label' => 'Content', 'type' => 'html'],
            ['name' => 'images', 'label' => 'Photos (up to 3)', 'type' => 'images', 'max' => 3],
            ...$button('button', 'Button'),
            ['name' => 'video_url', 'label' => 'YouTube video URL', 'type' => 'url'],
            ['name' => 'video_label', 'label' => 'Video label', 'type' => 'text'],
            ['name' => 'video_duration', 'label' => 'Video duration (e.g. 2:30 minutes)', 'type' => 'text'],
        ]],
        'statistics' => ['label' => 'Statistics bar', 'fields' => [
            ['name' => 'group', 'label' => 'Statistics group', 'type' => 'text', 'help' => 'Group name from Homepage > Statistics (e.g. "home").'],
            ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => [
                ['value' => 'plain', 'label' => 'Plain bar'],
                ['value' => 'overlap', 'label' => 'Overlapping the banner (use as first section)'],
                ['value' => 'sidebar', 'label' => 'Sidebar card (list pages)'],
            ]],
        ]],
        'feature_cards' => ['label' => 'Feature cards', 'fields' => [
            ['name' => 'style', 'label' => 'Layout', 'type' => 'select', 'options' => [
                ['value' => 'large', 'label' => 'Large cards (e.g. Mission / Vision)'],
                ['value' => 'grid', 'label' => 'Grid of small cards'],
                ['value' => 'steps', 'label' => 'Numbered process steps'],
                ['value' => 'stats', 'label' => 'Highlight numbers (title = number, text = label)'],
                ['value' => 'bar', 'label' => 'Highlights bar (overlaps the banner)'],
            ]],
            $featureItems,
        ]],
        'features_with_image' => ['label' => 'Features + photo', 'fields' => [
            $featureItems,
            ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
            ['name' => 'image_caption', 'label' => 'Photo caption (one line per row)', 'type' => 'textarea'],
        ]],
        'checklist' => ['label' => 'Checklist + photo', 'fields' => [
            ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
            ['name' => 'items', 'label' => 'Points', 'type' => 'repeater', 'max' => 12, 'fields' => [
                ['name' => 'text', 'label' => 'Text', 'type' => 'text', 'required' => true],
            ]],
        ]],
        'timeline' => ['label' => 'Company timeline', 'fields' => []],
        'team' => ['label' => 'Team members', 'fields' => [
            ['name' => 'limit', 'label' => 'How many to show', 'type' => 'number'],
            ...$button('link', 'Link'),
        ]],
        'partners' => ['label' => 'Partner logos', 'fields' => [
            ['name' => 'category', 'label' => 'Only this partner category (optional)', 'type' => 'text'],
        ]],
        'testimonials' => ['label' => 'Testimonials', 'fields' => [
            ['name' => 'limit', 'label' => 'How many to show', 'type' => 'number'],
        ]],
        'faq' => ['label' => 'FAQs', 'fields' => [
            ['name' => 'page_key', 'label' => 'FAQ group (page key)', 'type' => 'text', 'help' => 'Shows FAQs whose "Show on page" matches this value.'],
            ...$button('link', 'Link'),
        ]],
        'cta' => ['label' => 'Call to action banner', 'fields' => [
            ...$button('primary', 'Primary button'),
            ...$button('secondary', 'Secondary button'),
            ['name' => 'background_image', 'label' => 'Background photo', 'type' => 'image'],
            ['name' => 'side_image', 'label' => 'Side photo (cut-out)', 'type' => 'image'],
        ]],
    ],

    // Fixed homepage sections (key => editable data fields). Order / visibility are set in the admin.
    'homepage' => [
        'hero' => ['label' => 'Hero slider', 'note' => 'Slides are managed under Homepage > Hero Slides.', 'fields' => []],
        'statistics' => ['label' => 'Statistics bar', 'note' => 'Numbers are managed under Homepage > Statistics (group "home").', 'fields' => []],
        'destinations' => ['label' => 'Popular destinations', 'note' => 'Shows featured destinations.', 'fields' => []],
        'services' => ['label' => 'Core services', 'note' => 'Shows featured services.', 'fields' => []],
        'universities' => ['label' => 'Featured universities', 'note' => 'Shows featured universities.', 'fields' => [
            ['name' => 'background_image', 'label' => 'Background photo', 'type' => 'image'],
        ]],
        'pathways' => ['label' => 'Study pathways', 'fields' => [
            ['name' => 'items', 'label' => 'Pathways', 'type' => 'repeater', 'max' => 8, 'fields' => [
                ['name' => 'icon', 'label' => 'Icon', 'type' => 'icon'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true],
                ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
                ['name' => 'url', 'label' => 'Link', 'type' => 'url'],
            ]],
        ]],
        'why_choose' => ['label' => 'Why choose us', 'fields' => [
            ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
            ['name' => 'items', 'label' => 'Points', 'type' => 'repeater', 'max' => 10, 'fields' => [
                ['name' => 'text', 'label' => 'Text', 'type' => 'text', 'required' => true],
            ]],
        ]],
        'success_stories' => ['label' => 'Success stories', 'note' => 'Shows featured success stories that have a quote.', 'fields' => []],
        'testimonials' => ['label' => 'Testimonials', 'note' => 'Shows featured testimonials.', 'fields' => []],
        'gallery' => ['label' => 'Photo & video gallery', 'note' => 'Eyebrow / title / subtitle / button apply to the photo column.', 'fields' => [
            ['name' => 'video_eyebrow', 'label' => 'Video column eyebrow', 'type' => 'text'],
            ['name' => 'video_title', 'label' => 'Video column title', 'type' => 'text'],
            ['name' => 'video_subtitle', 'label' => 'Video column subtitle', 'type' => 'text'],
            ...$button('video_button', 'Video column link'),
        ]],
        'latest_news' => ['label' => 'Latest news + appointment form', 'note' => 'Shows the latest published blog posts.', 'fields' => [
            ['name' => 'form_title', 'label' => 'Form title', 'type' => 'text'],
            ['name' => 'form_subtitle', 'label' => 'Form subtitle', 'type' => 'textarea'],
            ['name' => 'form_button', 'label' => 'Form button text', 'type' => 'text'],
        ]],
        'partners' => ['label' => 'Partners', 'note' => 'Shows published partners.', 'fields' => []],
        'cta' => ['label' => 'Call to action banner', 'fields' => [
            ...$button('primary', 'Primary button'),
            ...$button('secondary', 'Secondary button'),
            ['name' => 'background_image', 'label' => 'Background photo', 'type' => 'image'],
            ['name' => 'side_image', 'label' => 'Side photo (cut-out)', 'type' => 'image'],
        ]],
        'newsletter' => ['label' => 'Newsletter signup', 'fields' => [
            ['name' => 'button_label', 'label' => 'Button text', 'type' => 'text'],
            ['name' => 'placeholder', 'label' => 'Email placeholder', 'type' => 'text'],
        ]],
    ],
];
