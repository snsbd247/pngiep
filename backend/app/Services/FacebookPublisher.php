<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Jobs\PublishToFacebook;
use App\Models\Blog;
use App\Models\News;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Posts published News articles and Blog posts to the company's Facebook Page
 * (Graph API, Page access token from Settings › Facebook). With an image it makes a
 * photo post (image + caption with the link); without one, a link post.
 *
 * An entry is posted once: when it is published (or its scheduled date arrives), its
 * "Post to Facebook" box is ticked, it has no post id yet and no earlier error.
 */
class FacebookPublisher
{
    /** Models that can be posted, keyed by the admin resource name. */
    public const MODELS = ['news' => News::class, 'blogs' => Blog::class];

    public function __construct(private SettingService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('facebook_autopost_enabled') && $this->configured();
    }

    public function configured(): bool
    {
        return filled($this->settings->get('facebook_page_id')) && filled($this->settings->get('facebook_page_token'));
    }

    /** Waiting to be posted (published, date reached, opted in, not yet posted, no error). */
    public function isDue(Model $entry): bool
    {
        return $entry->facebook_post
            && ! $entry->facebook_post_id
            && ! $entry->facebook_error
            && ! $entry->trashed()
            && ($entry->status?->value ?? $entry->status) === ContentStatus::Published->value
            && (! $entry->published_at || $entry->published_at->lte(now()));
    }

    /** Queue the post if auto-posting is on and the entry is due (no-op otherwise). */
    public function queueIfDue(Model $entry): bool
    {
        if (! $this->enabled() || ! $this->isDue($entry)) {
            return false;
        }

        PublishToFacebook::dispatch($entry::class, $entry->getKey());

        return true;
    }

    /** Due entries of every postable model, for the every-minute scheduler. */
    public function queueDue(): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $count = 0;
        foreach (self::MODELS as $model) {
            $model::query()->published()->where('facebook_post', true)->whereNull('facebook_post_id')->whereNull('facebook_error')
                ->orderBy('published_at')->limit(20)->get()
                ->each(function (Model $entry) use (&$count) {
                    $count += (int) $this->queueIfDue($entry);
                });
        }

        return $count;
    }

    /** Post now (called by the queued job). Returns the Facebook post id. */
    public function publish(Model $entry): string
    {
        $pageId = $this->settings->get('facebook_page_id');
        $link = $this->link($entry);
        $message = $this->caption($entry, $link);
        $image = $this->imageUrl($entry->featured_image);

        $data = $image
            ? $this->graph('POST', "{$pageId}/photos", ['url' => $image, 'caption' => $message])
            : $this->graph('POST', "{$pageId}/feed", ['message' => $message, 'link' => $link]);

        $id = (string) ($data['post_id'] ?? $data['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Facebook did not return a post id.');
        }

        return $id;
    }

    /** Settings › Facebook "Test connection": returns the Page name. */
    public function testConnection(): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('Enter the Page ID and Page access token and save first.');
        }

        $page = $this->graph('GET', (string) $this->settings->get('facebook_page_id'), ['fields' => 'id,name']);

        return (string) ($page['name'] ?? $page['id'] ?? 'Facebook Page');
    }

    public function link(Model $entry): string
    {
        $base = rtrim((string) (config('app.frontend_url') ?: config('app.url')), '/');

        return $base.($entry instanceof News ? '/news/' : '/blog/').$entry->slug;
    }

    public function caption(Model $entry, string $link): string
    {
        $template = (string) ($this->settings->get('facebook_caption') ?: "{title}\n\n{excerpt}\n\nRead more: {url}");
        $excerpt = $entry->excerpt ?: Str::limit(trim(html_entity_decode(strip_tags((string) $entry->content))), 300);

        $text = strtr($template, ['{title}' => $entry->title, '{excerpt}' => (string) $excerpt, '{url}' => $link]);

        // Drop blank lines left by an empty placeholder.
        return trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $text)));
    }

    /**
     * Public URL of the image for Facebook to download. Uploaded WebP images get a JPEG
     * copy, because not every Facebook upload path accepts WebP.
     */
    public function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $disk = Storage::disk('public');
        $path = ltrim($path, '/');

        if (Str::endsWith(Str::lower($path), '.webp') && $disk->exists($path) && function_exists('imagecreatefromwebp')) {
            $jpeg = 'facebook/'.sha1($path).'.jpg';
            if (! $disk->exists($jpeg) && ($img = @imagecreatefromwebp($disk->path($path)))) {
                $disk->makeDirectory('facebook');
                imagejpeg($img, $disk->path($jpeg), 88);
                imagedestroy($img);
            }
            if ($disk->exists($jpeg)) {
                $path = $jpeg;
            }
        }

        return rtrim((string) config('app.url'), '/').'/storage/'.$path;
    }

    private function graph(string $method, string $path, array $params): array
    {
        $c = config('services.facebook');
        $url = "{$c['graph_url']}/{$c['graph_version']}/".ltrim($path, '/');
        $params['access_token'] = (string) $this->settings->get('facebook_page_token');

        try {
            $request = Http::timeout($c['timeout'])->acceptJson();
            $response = $method === 'GET' ? $request->get($url, $params) : $request->asForm()->post($url, $params);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not reach Facebook.');
        }

        $body = $response->json() ?? [];
        if ($response->failed() || isset($body['error'])) {
            $error = $body['error']['message'] ?? "Facebook request failed (HTTP {$response->status()}).";
            if (($body['error']['code'] ?? null) === 190) {
                $error = 'The Facebook Page token is invalid or expired. Create a new one and save it in Settings › Facebook. ('.$error.')';
            }

            throw new RuntimeException(Str::limit($error, 480));
        }

        return $body;
    }
}
