<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

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
        {{-- If app never mounts (white screen), after 12s show message or last stored error --}}
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
                            var last = '';
                            try { last = sessionStorage.getItem('inertia_last_error') || ''; } catch (_) {}
                            if (last) {
                                app.innerHTML = '<div style="padding:2rem;font-family:monospace;font-size:13px;background:#1e293b;color:#f1f5f9;white-space:pre-wrap;min-height:100vh;">' +
                                    '<strong style="color:#f87171;">Last error (copy and send this):</strong><br><br>' + last.replace(/</g, '&lt;') + '</div>';
                            } else {
                                app.innerHTML = '<div style="padding:2rem;text-align:center;font-family:sans-serif;color:#333;">' +
                                    '<p style="font-size:1.1rem;">Page did not load. Open F12 → Console and send a screenshot of red errors.</p></div>';
                            }
                        }
                    }
                }
                window.addEventListener('load', function() { window.__inertiaLoadStart = Date.now(); });
                setTimeout(check, 12000);
            })();
        </script>
    </body>
</html>
