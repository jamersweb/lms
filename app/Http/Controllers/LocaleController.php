<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Update the current user's preferred locale and session locale.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,ur'],
            'content_locale' => ['nullable', 'string', 'in:en,ur'],
        ]);

        $locale = $validated['locale'];
        $contentLocale = $validated['content_locale'] ?? $locale;

        // Persist on user (if logged in)
        if ($request->user()) {
            $request->user()->forceFill([
                'locale' => $locale,
                'content_locale' => $contentLocale,
            ])->save();
        }

        // Persist in session
        $request->session()->put('locale', $locale);
        $request->session()->put('content_locale', $contentLocale);

        app()->setLocale($locale);

        return back();
    }
}

