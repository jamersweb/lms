<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScheduledTriggersJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * HTTP endpoint for scheduled triggers. No server cron needed.
 * Use cron-job.org, EasyCron, or UptimeRobot to hit this URL every hour.
 */
class SchedulerController extends Controller
{
    /**
     * Run scheduled triggers. Protected by token.
     * GET /scheduler/run?token=your-secret-token
     */
    public function run(Request $request): Response
    {
        $token = config('app.scheduler_token');
        if (empty($token)) {
            Log::warning('SchedulerController: SCHEDULER_TOKEN not configured');
            return response('Scheduler not configured', 503);
        }

        if ($request->query('token') !== $token) {
            return response('Unauthorized', 401);
        }

        try {
            // Run synchronously so the HTTP client gets a response after work is done
            (new ProcessScheduledTriggersJob)->handle();
            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('SchedulerController failed', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    }
}
