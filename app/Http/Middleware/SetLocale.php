<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Priority: explicit user preference -> session -> app default
        $locale = $user?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale', 'en');

        app()->setLocale($locale);

        return $next($request);
    }
}

