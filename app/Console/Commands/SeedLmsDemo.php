<?php

namespace App\Console\Commands;

use Database\Seeders\LmsDemoSeeder;
use Illuminate\Console\Command;

class SeedLmsDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:seed-demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo data for Phase 1 content gating features (users, courses with rules)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Safety check: only allow in local/dev
        if (!app()->environment('local') && !config('app.debug')) {
            $this->error('This command should only run in local/dev environments.');
            $this->warn('Set APP_ENV=local or APP_DEBUG=true to proceed.');
            return Command::FAILURE;
        }

        $this->info('Seeding Phase 1 demo data...');

        $seeder = new LmsDemoSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('Demo data seeded successfully!');
        $this->newLine();
        $this->info('Demo users:');
        $this->line('  - beginner_male@demo.com (password: password)');
        $this->line('  - expert_female@demo.com (password: password)');
        $this->line('  - admin@demo.com (password: password)');

        return Command::SUCCESS;
    }
}
