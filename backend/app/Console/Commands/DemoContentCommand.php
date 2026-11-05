<?php

namespace App\Console\Commands;

use App\Support\DemoContent;
use Illuminate\Console\Command;

/**
 *   php artisan app:demo-content          show what demo content remains
 *   php artisan app:demo-content remove   delete it (asks for confirmation; add --force to skip)
 */
class DemoContentCommand extends Command
{
    protected $signature = 'app:demo-content {action=list : list or remove} {--force : Remove without asking}';

    protected $description = 'List or remove the demo courses, stories, testimonials, gallery and blog posts';

    public function handle(): int
    {
        if (! DemoContent::manifest()) {
            $this->info('No demo content is installed.');

            return self::SUCCESS;
        }

        $counts = DemoContent::counts();
        $this->table(['Content', 'Demo records'], collect($counts)->map(fn ($n, $k) => [str_replace('_', ' ', $k), $n])->values());

        if ($this->argument('action') !== 'remove') {
            $this->line('Remove it with: php artisan app:demo-content remove');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete all demo content? Real content you added is not touched.')) {
            return self::FAILURE;
        }

        $this->info('Removed '.DemoContent::remove().' demo records.');

        return self::SUCCESS;
    }
}
