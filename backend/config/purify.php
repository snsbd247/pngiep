<?php

use App\Support\PurifyDefinition;

/*
 * HTML allowed in rich-text content (pages, blog, news, success stories, section HTML).
 * Same rules as the package default, plus YouTube embeds from the editor:
 * <div data-youtube-video><iframe src="https://www.youtube-nocookie.com/embed/ID"></iframe></div>.
 * Any other iframe source is removed.
 */
return [
    'default' => 'default',

    'configs' => [
        'default' => [
            'Core.Encoding' => 'utf-8',
            'HTML.Doctype' => 'HTML 4.01 Transitional',
            'HTML.Allowed' => 'h1,h2,h3,h4,h5,h6,b,u,strong,i,em,s,del,a[href|title],ul,ol,li,p[style],br,span,img[width|height|alt|src],blockquote,'
                .'div[data-youtube-video],iframe[src|width|height|title|allowfullscreen|frameborder]',
            'HTML.ForbiddenElements' => '',
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '#^https://www\.(youtube|youtube-nocookie)\.com/embed/[A-Za-z0-9_-]{11}(\?[A-Za-z0-9_=&;.%-]*)?$#',
            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,text-decoration,padding-left,color,background-color,text-align',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
        ],
    ],

    'definitions' => PurifyDefinition::class,

    'css-definitions' => null,

    'serializer' => [
        'driver' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
        'cache' => \Stevebauman\Purify\Cache\CacheDefinitionCache::class,
    ],
];
