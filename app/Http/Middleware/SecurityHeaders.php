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
            // Development: Allow unsafe-inline/unsafe-eval for Vite HMR
            // [::1] is invalid in CSP source lists in many browsers; localhost + 127.0.0.1 cover dev
            $response->headers->set('Content-Security-Policy', "default-src 'self' data: blob: http: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: http://localhost:5173 http://127.0.0.1:5173 https:; style-src 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173 https:; connect-src 'self' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173 * ws: wss:; font-src 'self' data: https:; img-src 'self' data: blob: http: https:;");
        } else {
            // Production: Strict CSP without unsafe-inline/unsafe-eval
            // Note: For production, you should use nonces or hashes for inline scripts
            // This CSP allows YouTube embeds and common CDNs while maintaining security
            $csp = "default-src 'self' data: blob: https:; " .
                   "script-src 'self' 'strict-dynamic' https: *.youtube.com *.youtube-nocookie.com *.googleapis.com; " .
                   "style-src 'self' 'unsafe-inline' https:; " . // unsafe-inline needed for Tailwind/Vue
                   "connect-src 'self' https: wss: ws:; " .
                   "font-src 'self' data: https:; " .
                   "img-src 'self' data: blob: https: *.youtube.com *.youtube-nocookie.com *.ytimg.com; " .
                   "frame-src 'self' https: *.youtube.com *.youtube-nocookie.com *.vimeo.com; " .
                   "media-src 'self' blob: https:;";
            
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
