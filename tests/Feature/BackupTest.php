<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class BackupTest extends TestCase
{
    use DatabaseMigrations;

    public function test_backup_can_be_restored_to_a_separate_sqlite_database(): void
    {
        User::factory()->create(['email' => 'restore-test@example.test']);
        $original = storage_path();
        $root = sys_get_temp_dir().'/lms-backup-test-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/app/public');
        File::put($root.'/app/public/resource.txt', 'Backup test resource');
        $this->app->useStoragePath($root);
        config(['backup.path' => $root.'/backups']);
        try {
            $this->artisan('lms:backup')->assertSuccessful();
            $archives = glob($root.'/backups/*.zip');
            $this->assertCount(1, $archives);
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($archives[0]));
            $this->assertSame('Backup test resource', $zip->getFromName('uploads/public/resource.txt'));
            File::put($root.'/restored.sqlite', $zip->getFromName('database.sqlite'));
            $zip->close();
            $restored = new \PDO('sqlite:'.$root.'/restored.sqlite');
            $this->assertSame('ok', $restored->query('PRAGMA integrity_check')->fetchColumn());
            $this->assertSame('restore-test@example.test', $restored->query('SELECT email FROM users')->fetchColumn());
            unset($restored);
        } finally {
            $this->app->useStoragePath($original);
            File::deleteDirectory($root);
        }
    }
}
