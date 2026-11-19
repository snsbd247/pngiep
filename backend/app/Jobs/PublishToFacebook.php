<?php

namespace App\Jobs;

use App\Services\AdminNotifier;
use App\Services\FacebookPublisher;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Posts one News article / Blog post to the Facebook Page. Unique per entry, 3 tries. */
class PublishToFacebook implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public int $uniqueFor = 900;

    /** @param  class-string<Model>  $model */
    public function __construct(public string $model, public int $id) {}

    public function uniqueId(): string
    {
        return $this->model.':'.$this->id;
    }

    public function handle(FacebookPublisher $facebook): void
    {
        // A lock as well as ShouldBeUnique: a manual "Post now" and the scheduler must never both post.
        Cache::lock('facebook-post:'.$this->uniqueId(), 120)->block(10, function () use ($facebook) {
            $entry = $this->model::query()->find($this->id);
            if (! $entry || ! $facebook->configured() || ! $facebook->isDue($entry)) {
                return;
            }

            $postId = $facebook->publish($entry);
            $entry->forceFill(['facebook_post_id' => $postId, 'facebook_posted_at' => now(), 'facebook_error' => null])->saveQuietly();
        });
    }

    /** After the last try: keep the reason (stops further automatic tries) and tell the admins. */
    public function failed(?Throwable $e): void
    {
        $entry = $this->model::query()->find($this->id);
        if (! $entry) {
            return;
        }

        $message = $e?->getMessage() ?: 'Unknown error.';
        $entry->forceFill(['facebook_error' => mb_substr($message, 0, 500)])->saveQuietly();

        $resource = str_contains($this->model, 'News') ? 'news' : 'blogs';
        app(AdminNotifier::class)->notify('social', 'Facebook post failed: '.$entry->title, $message, "/admin/content/{$resource}/{$entry->id}");
    }
}
