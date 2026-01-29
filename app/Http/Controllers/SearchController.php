<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request, SearchService $searchService)
    {
        $query = (string) $request->input('q', '');
        $query = trim($query);

        $results = mb_strlen($query) >= 2 ? $searchService->search($query) : [];

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
