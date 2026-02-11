<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

        <!-- Fonts loaded via app.css (Google Fonts: Inter, Playfair Display) -->

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        {{-- If app never mounts (white screen), show message after 12s and log any early errors --}}
        <script>
            (function() {
                var shown = false;
                function check() {
                    if (shown) return;
                    var app = document.getElementById('app');
                    var err = document.getElementById('inertia-error-overlay');
                    if (app && !err && (!app.innerHTML || app.innerHTML.trim().length < 50)) {
                        var t = (window.__inertiaLoadStart && (Date.now() - window.__inertiaLoadStart > 12000)) ? true : false;
                        if (t || (document.readyState === 'complete' && !document.querySelector('[data-page]') && !app.textContent)) {
                            shown = true;
                            app.innerHTML = '<div style="padding:2rem;text-align:center;font-family:sans-serif;color:#333;">' +
                                '<p style="font-size:1.1rem;">Page did not load. Open Developer Tools (F12) → Console tab and check for red errors.</p>' +
                                '<p style="margin-top:1rem;font-size:0.9rem;color:#666;">Send a screenshot of the Console errors to fix the issue.</p></div>';
                        }
                    }
                }
                window.addEventListener('load', function() { window.__inertiaLoadStart = Date.now(); });
                setTimeout(check, 12000);
            })();
        </script>
    </body>
</html>
