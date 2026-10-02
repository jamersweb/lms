<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupCommand extends Command
{
    protected $signature = 'lms:backup';
    protected $description = 'Create and verify a private database and uploaded-files backup';

    public function handle(): int
    {
        $directory = config('backup.path');
        File::ensureDirectoryExists($directory, 0700);
        $directory = realpath($directory);
        $public = realpath(public_path());
        if (!$directory || str_starts_with(strtolower(str_replace('\\', '/', $directory)).'/', strtolower(str_replace('\\', '/', $public)).'/')) {
            $this->error('Backup directory must be outside the web root.');
            return self::FAILURE;
        }
        $stem = $directory.'/lms-'.now()->format('Ymd-His').'-'.Str::random(8);
        $dump = $stem.'.db';
        $archive = $stem.'.zip';
        $zip = new ZipArchive;
        try {
            $connection = DB::connection();
            $config = $connection->getConfig();
            $driver = $connection->getDriverName();
            if ($driver === 'sqlite') {
                $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($dump));
                $probe = new \PDO('sqlite:'.$dump);
                if ($probe->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
                    throw new \RuntimeException('SQLite backup integrity check failed.');
                }
                unset($probe);
            } elseif (in_array($driver, ['mysql', 'mariadb'])) {
                $process = new Process([
                    config('backup.mysqldump'), '--single-transaction', '--quick', '--hex-blob',
                    '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'],
                    '--result-file='.$dump, $config['database'],
                ], null, ['MYSQL_PWD' => $config['password'] ?? '']);
                $process->setTimeout(1800)->mustRun();
            } else {
                throw new \RuntimeException('Backup supports MySQL, MariaDB and SQLite only.');
            }
            chmod($dump, 0600);
            if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
                throw new \RuntimeException('Cannot create backup archive.');
            }
            $files = ['database.'.($driver === 'sqlite' ? 'sqlite' : 'sql') => $dump];
            foreach (['public', 'private'] as $disk) {
                $root = storage_path('app/'.$disk);
                if (is_dir($root)) {
                    foreach (File::allFiles($root) as $file) {
                        if (!$file->isLink()) {
                            $files['uploads/'.$disk.'/'.$file->getRelativePathname()] = $file->getPathname();
                        }
                    }
                }
            }
            $hashes = [];
            foreach ($files as $name => $path) {
                $name = str_replace('\\', '/', $name);
                $hashes[$name] = hash_file('sha256', $path);
                if (!$zip->addFile($path, $name)) {
                    throw new \RuntimeException('Cannot add backup member.');
                }
            }
            $zip->addFromString('manifest.json', json_encode(['driver' => $driver, 'created_at' => now()->toIso8601String(), 'sha256' => $hashes], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            if (!$zip->close() || $zip->open($archive, ZipArchive::CHECKCONS) !== true) {
                throw new \RuntimeException('Backup verification failed.');
            }
            foreach ($hashes as $name => $hash) {
                $stream = $zip->getStream($name);
                $context = hash_init('sha256');
                if (!$stream) { throw new \RuntimeException('Missing backup member.'); }
                hash_update_stream($context, $stream);
                fclose($stream);
                if (!hash_equals($hash, hash_final($context))) {
                    throw new \RuntimeException('Backup checksum mismatch.');
                }
            }
            $zip->close();
            chmod($archive, 0600);
            $this->info('Verified backup: '.$archive);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Backup failed. Check private application logs.');
            return self::FAILURE;
        } finally {
            if (is_file($dump)) { unlink($dump); }
        }
    }
}
