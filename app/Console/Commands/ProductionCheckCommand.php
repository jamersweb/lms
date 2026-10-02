<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionCheckCommand extends Command
{
    protected $signature = 'lms:production-check';
    protected $description = 'Check deployment configuration without modifying application data';

    public function handle(): int
    {
        $checks = [
            'Production environment' => app()->environment('production'),
            'Debug disabled' => !config('app.debug'),
            'HTTPS application URL' => str_starts_with(config('app.url', ''), 'https://'),
            'Secure session cookies' => (bool) config('session.secure'),
            'Persistent queue' => !in_array(config('queue.default'), ['sync', 'null']),
            'Persistent cache' => !in_array(config('cache.default'), ['array', 'null']),
            'Application key configured' => filled(config('app.key')),
            'Public database exporter removed' => !file_exists(public_path('export-database.php')),
            'No Vite development marker' => !file_exists(public_path('hot')),
            'Production build present' => file_exists(public_path('build/manifest.json')),
            'Storage writable' => is_writable(storage_path()),
        ];
        try {
            DB::connection()->getPdo();
            $checks['Database connection'] = true;
        } catch (\Throwable $e) {
            $checks['Database connection'] = false;
        }
        $this->table(['Check', 'Result'], collect($checks)->map(fn ($ok, $name) => [$name, $ok ? 'PASS' : 'FAIL'])->all());
        $this->line('Also verify worker uptime, off-server backups, restore recovery, and real email/WhatsApp delivery on the target server.');
        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
