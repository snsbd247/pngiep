<?php

namespace App\Support;

use HTMLPurifier_HTMLDefinition;
use Stevebauman\Purify\Definitions\Html5Definition;

/**
 * HTML5 elements (package default) plus what the rich-text editor's YouTube embed needs:
 * the `<div data-youtube-video>` wrapper (so the editor can load the video again) and
 * `allowfullscreen` on the iframe. Which iframe sources are allowed is limited by
 * URI.SafeIframeRegexp in config/purify.php (YouTube embed URLs only).
 */
class PurifyDefinition extends Html5Definition
{
    public static function apply(HTMLPurifier_HTMLDefinition $definition)
    {
        parent::apply($definition);

        $definition->addAttribute('div', 'data-youtube-video', 'Text');
        $definition->addAttribute('iframe', 'allowfullscreen', 'Bool');
        // src is required: an iframe whose source was rejected is dropped instead of left empty.
        $definition->addAttribute('iframe', 'src*', 'URI#embedded');
    }
}
