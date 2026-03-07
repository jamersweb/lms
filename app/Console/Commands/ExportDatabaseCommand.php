<?php

namespace App\Console\Commands;

use App\Services\DatabaseExportService;
use Illuminate\Console\Command;

class ExportDatabaseCommand extends Command
{
    protected $signature = 'db:export {--path= : Directory to save the file (default: storage/app/backups)}';

    protected $description = 'Export the database to a SQL file (MySQL/MariaDB).';

    public function handle(DatabaseExportService $exporter): int
    {
        $path = $this->option('path') ?? storage_path('app/backups');
        $this->info('Exporting database...');

        try {
            $file = $exporter->exportToFile($path);
            $this->info('Export saved to: ' . $file);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Export failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
