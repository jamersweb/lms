<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyOpenClawToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('whatsapp.openclaw_api_token');

        if (empty($configuredToken)) {
            return response()->json([
                'message' => 'OpenClaw API token is not configured.',
            ], 503);
        }

        $providedToken = $request->bearerToken() ?: $request->header('X-OpenClaw-Token');

        if (!hash_equals($configuredToken, (string) $providedToken)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}

