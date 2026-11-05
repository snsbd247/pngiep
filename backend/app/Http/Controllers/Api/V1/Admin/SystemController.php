<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\ApiResponse;
use App\Support\DemoContent;
use App\Support\PublicCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Settings › System: health information and safe maintenance actions for administrators. */
class SystemController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function info(Request $request): JsonResponse
    {
        $backups = $this->backupFiles();

        return ApiResponse::success([
            'app' => [
                'name' => config('app.name'), 'environment' => app()->environment(), 'debug' => (bool) config('app.debug'),
                'url' => config('app.url'), 'timezone' => config('app.timezone'),
                'php' => PHP_VERSION, 'laravel' => app()->version(),
                'database' => DB::connection()->getDriverName().' '.$this->dbVersion(),
                'config_cached' => app()->configurationIsCached(), 'routes_cached' => app()->routesAreCached(),
            ],
            'mail' => ['mailer' => config('mail.default'), 'host' => config('mail.mailers.smtp.host'), 'from' => config('mail.from.address')],
            'queue' => [
                'connection' => config('queue.default'),
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
                // The scheduler drains the queue every minute; old pending jobs mean the cron job is not running.
                'oldest_pending_minutes' => ($t = DB::table('jobs')->min('created_at')) ? (int) floor((time() - (int) $t) / 60) : null,
            ],
            'storage' => [
                'media' => $this->dirSize(storage_path('app/public')),
                'documents' => $this->dirSize(storage_path('app/private/documents')),
                'backups' => array_sum(array_column($backups, 'size')),
                'disk_free' => @disk_free_space(storage_path()) ?: null,
            ],
            'last_backup' => $backups[0] ?? null,
            'demo_content' => DemoContent::manifest() ? array_sum(DemoContent::counts()) : 0,
            'can_download_backups' => $request->user()->isSuperAdmin(),
        ]);
    }

    /** Clears compiled caches and the public content cache, then rebuilds the production caches. */
    public function clearCache(Request $request): JsonResponse
    {
        Artisan::call('optimize:clear');
        PublicCache::flush();
        if (app()->isProduction()) {
            Artisan::call('optimize');
        }
        $this->logger->log('cache_clear', 'system', description: 'Cleared application caches');

        return ApiResponse::success(null, 'Caches cleared.');
    }

    public function backup(Request $request): JsonResponse
    {
        $request->validate(['files' => ['boolean']]);
        @set_time_limit(300);
        Artisan::call('app:backup', $request->boolean('files') ? ['--files' => true] : []);
        $this->logger->log('backup', 'system', description: 'Created a backup'.($request->boolean('files') ? ' (database + files)' : ' (database)'));

        return ApiResponse::success($this->backupFiles(), 'Backup created.');
    }

    public function backups(): JsonResponse
    {
        return ApiResponse::success($this->backupFiles());
    }

    /** Backups contain every student's data: Super Admin only, and every download is logged. */
    public function download(Request $request, string $file): BinaryFileResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $path = storage_path('app/backups/'.basename($file));
        abort_unless(preg_match('/^(db|files)_[0-9_-]+\.(sql\.gz|zip)$/', basename($file)) && is_file($path), 404);
        $this->logger->log('download', 'system', description: "Downloaded backup {$file}");

        return response()->download($path, basename($file), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** @return list<array{name: string, size: int, created_at: string, type: string}> newest first */
    private function backupFiles(): array
    {
        $dir = storage_path('app/backups');
        if (! is_dir($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => preg_match('/^(db|files)_/', $f->getFilename()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => ['name' => $f->getFilename(), 'size' => $f->getSize(), 'created_at' => date(DATE_ATOM, $f->getMTime()), 'type' => str_starts_with($f->getFilename(), 'db_') ? 'database' : 'files'])
            ->values()->all();
    }

    private function dirSize(string $dir): int
    {
        return is_dir($dir) ? array_sum(array_map(fn ($f) => $f->getSize(), File::allFiles($dir))) : 0;
    }

    private function dbVersion(): string
    {
        try {
            return (string) DB::selectOne('select version() as v')->v;
        } catch (\Throwable) {
            return '';
        }
    }
}
