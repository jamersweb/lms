<?php

namespace App\Console\Commands;

use App\Jobs\AggregateDailyUserMetricsJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AggregateDailyMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:aggregate-daily-metrics {--date= : Date to aggregate (Y-m-d format, defaults to yesterday)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate daily user metrics for analytics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateInput = $this->option('date');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::yesterday();

        $this->info("Aggregating daily metrics for {$date->toDateString()}...");

        $job = new AggregateDailyUserMetricsJob($date);
        $job->handle();

        $this->info('Daily metrics aggregated successfully.');

        return Command::SUCCESS;
    }
}
