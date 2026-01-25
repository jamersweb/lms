<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\PointsService;

class JournalController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display journal entries for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get today's entry
        $todayEntry = $user->journalEntries()
            ->whereDate('entry_date', today())
            ->first();
        
        // Get recent entries (excluding today)
        $entries = $user->journalEntries()
            ->whereDate('entry_date', '<', today())
            ->orderBy('entry_date', 'desc')
            ->limit(10)
            ->get()
            ->map(function($entry) {
                return [
                    'id' => $entry->id,
                    'entry_date' => $entry->entry_date,
                    'mood' => $entry->mood,
                    'content' => $entry->content,
                    'created_at' => $entry->created_at->diffForHumans()
                ];
            });

        return Inertia::render('Journal/Index', [
            'entries' => ['data' => $entries],
            'todayEntry' => $todayEntry ? [
                'entry_date' => $todayEntry->entry_date,
                'mood' => $todayEntry->mood,
                'content' => $todayEntry->content
            ] : null,
        ]);
    }

    /**
     * Store or update a journal entry.
     */
    public function store(StoreJournalEntryRequest $request)
    {
        $validated = $request->validated();

        $entryDate = $validated['entry_date'] ?? today()->toDateString();
        
        // Update or create entry for the date
        $user = auth()->user();
        $entry = $user->journalEntries()->updateOrCreate(
            ['entry_date' => $entryDate],
            [
                'content' => $validated['content'],
                'mood' => $validated['mood'] ?? 'neutral'
            ]
        );

        // Award points for new entries
        if ($entry->wasRecentlyCreated) {
            PointsService::award($user, 'journal_entry', 5);
        }

        return back()->with('success', 'Journal entry saved successfully!');
    }
}
