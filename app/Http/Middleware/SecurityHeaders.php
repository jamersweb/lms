<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Allow Vite dev server in development mode
        if (app()->environment('local')) {
            $response->headers->set('Content-Security-Policy', "default-src 'self' data: blob: http: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: http://localhost:5173 http://[::1]:5173 https:; style-src 'self' 'unsafe-inline' http://localhost:5173 http://[::1]:5173 https:; connect-src * ws: wss:; font-src 'self' data: https:; img-src 'self' data: blob: http: https:;");
        } else {
            $response->headers->set('Content-Security-Policy', "default-src 'self' data: blob: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https:; style-src 'self' 'unsafe-inline' https:; connect-src * ws: wss:; font-src 'self' data: https:; img-src 'self' data: blob: https:;");
        }

        return $response;
    }
}
