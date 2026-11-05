<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Shared-hosting friendly backup: pure-PHP SQL dump (no mysqldump binary needed),
 * optional zip of uploads + private application documents, and rotation.
 *
 *   php artisan app:backup              database only
 *   php artisan app:backup --files      database + storage files
 *
 * Backups go to storage/app/backups, which is outside the web root. Download them
 * with the cPanel File Manager and restore the .sql.gz with phpMyAdmin > Import.
 */
class BackupCommand extends Command
{
    protected $signature = 'app:backup {--files : Also archive uploaded media and application documents} {--keep=14 : Number of backups of each kind to keep}';

    protected $description = 'Back up the database (and optionally uploaded files) to storage/app/backups';

    private const CHUNK = 500;

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0750);
        if (! File::exists("{$dir}/.htaccess")) {
            File::put("{$dir}/.htaccess", "Require all denied\n");
        }
        $stamp = now()->format('Y-m-d_His');

        $sqlFile = "{$dir}/db_{$stamp}.sql.gz";
        $this->dumpDatabase($sqlFile);
        $this->info('Database: '.basename($sqlFile).' ('.$this->size($sqlFile).')');

        if ($this->option('files')) {
            $zipFile = "{$dir}/files_{$stamp}.zip";
            $count = $this->archiveFiles($zipFile);
            // ZipArchive writes nothing for an empty archive.
            $count && File::exists($zipFile)
                ? $this->info("Files: {$count} files in ".basename($zipFile).' ('.$this->size($zipFile).')')
                : $this->info('Files: nothing to archive yet.');
        }

        $this->rotate($dir, 'db_', (int) $this->option('keep'));
        $this->rotate($dir, 'files_', (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function dumpDatabase(string $path): void
    {
        $connection = DB::connection();
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('app:backup supports MySQL / MariaDB only.');
        }
        $pdo = $connection->getPdo();
        $out = gzopen($path, 'wb6') ?: throw new RuntimeException("Cannot write {$path}");

        try {
            gzwrite($out, "-- Backup of `{$connection->getDatabaseName()}` at ".now()->toDateTimeString()."\n");
            gzwrite($out, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$table]) {
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM)[1];
                gzwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n");

                // Keyset-free paging is fine at this scale; rows are streamed in chunks.
                for ($offset = 0; ; $offset += self::CHUNK) {
                    $rows = $pdo->query("SELECT * FROM `{$table}` LIMIT ".self::CHUNK." OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
                    if (! $rows) {
                        break;
                    }
                    $columns = '`'.implode('`, `', array_keys($rows[0])).'`';
                    $values = array_map(fn ($row) => '('.implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)).')', $rows);
                    gzwrite($out, "INSERT INTO `{$table}` ({$columns}) VALUES\n".implode(",\n", $values).";\n");
                }
                gzwrite($out, "\n");
            }
            gzwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            gzclose($out);
        }
    }

    private function archiveFiles(string $path): int
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required for --files.');
        }
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create {$path}");
        }
        $count = 0;
        foreach (['public' => storage_path('app/public'), 'documents' => storage_path('app/private/documents')] as $prefix => $root) {
            if (! File::isDirectory($root)) {
                continue;
            }
            foreach (File::allFiles($root) as $file) {
                $zip->addFile($file->getPathname(), $prefix.'/'.str_replace('\\', '/', $file->getRelativePathname()));
                $count++;
            }
        }
        $zip->close();

        return $count;
    }

    private function rotate(string $dir, string $prefix, int $keep): void
    {
        collect(File::files($dir))
            ->filter(fn ($f) => str_starts_with($f->getFilename(), $prefix))
            ->sortByDesc(fn ($f) => $f->getFilename())
            ->slice(max(1, $keep))
            ->each(fn ($f) => File::delete($f->getPathname()));
    }

    private function size(string $path): string
    {
        $bytes = File::size($path);

        return $bytes > 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024, 1).' KB';
    }
}
