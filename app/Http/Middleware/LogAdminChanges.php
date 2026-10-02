<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminChanges
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->user()?->is_admin && $request->is('admin/*') && !$request->isMethodSafe()
            && $response->getStatusCode() < 400 && !$request->session()->has('errors')) {
            app(ActivityLogger::class)->log('admin.change', $request->user(), ['meta' => [
                'route' => $request->route()?->getName(), 'method' => $request->method(),
                'path' => $request->path(),
                // Record field names, never passwords, tokens, uploaded content or student answers.
                'fields' => array_values(array_diff(array_keys($request->all()), ['password', 'password_confirmation', '_token', 'token'])),
            ]]);
        }
        return $response;
    }
}
