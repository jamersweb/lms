<?php

namespace App\Console\Commands;

use App\Jobs\UpdateCourseProgressSnapshotsJob;
use Illuminate\Console\Command;

class UpdateCourseSnapshotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:update-course-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update course progress snapshots for all enrolled users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating course progress snapshots...');

        $progressionService = app(\App\Services\ProgressionService::class);
        $releaseScheduleService = app(\App\Services\ReleaseScheduleService::class);

        $job = new UpdateCourseProgressSnapshotsJob();
        $job->handle($progressionService, $releaseScheduleService);

        $this->info('Course progress snapshots updated successfully.');

        return Command::SUCCESS;
    }
}
