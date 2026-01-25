<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $query = $request->input('q');

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $query ? [
                [
                    'id' => 1,
                    'type' => 'lesson',
                    'title' => 'The Importance of Intention',
                    'course' => 'Purification of the Heart',
                    'snippet' => '...verily actions are but by <b>intention</b>, and every man shall have but that which he intended...',
                    'url' => route('lessons.show', ['course' => 1, 'lesson' => 2]),
                    'timestamp' => '05:30'
                ],
                [
                    'id' => 2,
                    'type' => 'lesson',
                    'title' => 'Understanding Riyaa',
                    'course' => 'Purification of the Heart',
                    'snippet' => '...showing off corrupts the <b>intention</b> and renders the deed void...',
                    'url' => route('lessons.show', ['course' => 1, 'lesson' => 5]),
                    'timestamp' => '12:15'
                ]
            ] : []
        ]);
    }
}
