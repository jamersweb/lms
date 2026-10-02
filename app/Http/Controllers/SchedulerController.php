<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessScheduledTriggersJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * HTTP endpoint for scheduled triggers. No server cron needed.
 * Use cron-job.org, EasyCron, or UptimeRobot to hit this URL every hour.
 */
class SchedulerController extends Controller
{
    /**
     * Run scheduled triggers. Protected by token.
     * POST /scheduler/run with Authorization: Bearer <token>.
     */
    public function run(Request $request): Response
    {
        $token = config('app.scheduler_token');
        if (empty($token)) {
            Log::warning('SchedulerController: SCHEDULER_TOKEN not configured');
            return response('Scheduler not configured', 503);
        }

        if (!hash_equals((string) $token, (string) $request->bearerToken())) {
            return response('Unauthorized', 401);
        }

        $lock = Cache::lock('scheduled-triggers', 3600);
        if (!$lock->get()) {
            return response('Already running', 409);
        }

        try {
            // Run synchronously so the HTTP client gets a response after work is done
            (new ProcessScheduledTriggersJob)->handle();
            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('SchedulerController failed', ['error' => $e->getMessage()]);
            return response('Scheduler failed', 500);
        } finally {
            $lock->release();
        }
    }
}
