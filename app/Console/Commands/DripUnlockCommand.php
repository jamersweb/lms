<?php

namespace App\Console\Commands;

use App\Models\LessonProgress;
use App\Services\JourneyService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DripUnlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lms:drip-unlock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute lesson availability for drip-scheduled lessons';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        $this->info('Running drip unlock at '.$now->toDateTimeString());

        $progressToCheck = LessonProgress::where('status', 'locked')
            ->whereNotNull('available_at')
            ->where('available_at', '<=', $now)
            ->with(['lesson.module.course', 'user'])
            ->get();

        if ($progressToCheck->isEmpty()) {
            $this->info('No lessons to unlock.');
            return Command::SUCCESS;
        }

        $pairs = new Collection();

        foreach ($progressToCheck as $progress) {
            if (! $progress->lesson || ! $progress->lesson->module || ! $progress->lesson->module->course || ! $progress->user) {
                continue;
            }
            $pairs->push([
                'user' => $progress->user,
                'course' => $progress->lesson->module->course,
            ]);
        }

        $uniquePairs = $pairs->unique(fn ($item) => $item['user']->id.'-'.$item['course']->id);

        foreach ($uniquePairs as $pair) {
            JourneyService::computeStatusesForCourse($pair['user'], $pair['course']);
        }

        $this->info('Drip unlock completed for '.$uniquePairs->count().' user-course pairs.');

        return Command::SUCCESS;
    }
}
