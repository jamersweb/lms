<?php

namespace App\Http\Controllers;

use App\Models\DuaPrayer;
use App\Models\DuaRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DuaWallController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}
    public function index(Request $request)
    {
        $user = $request->user();

        // Only show active, non-deleted requests
        $query = DuaRequest::query()
            ->active()
            ->with('user')
            ->withCount('prayers')
            ->latest();

        // Efficiently check if current user has prayed for each request
        if ($user) {
            $prayedRequestIds = DuaPrayer::where('user_id', $user->id)
                ->pluck('dua_request_id')
                ->toArray();
        } else {
            $prayedRequestIds = [];
        }

        $requests = $query->paginate(20);

        $mapped = $requests->getCollection()->map(function (DuaRequest $dua) use ($user, $prayedRequestIds) {
            $hasPrayed = in_array($dua->id, $prayedRequestIds);

            return [
                'id' => $dua->id,
                'content' => $dua->content,
                'created_at' => $dua->created_at?->diffForHumans(),
                'is_anonymous' => $dua->is_anonymous,
                'author' => $dua->is_anonymous || ! $dua->user ? null : [
                    'name' => $dua->user->name,
                ],
                'prayers_count' => $dua->prayers_count ?? 0,
                'has_prayed' => $hasPrayed,
            ];
        });

        $requests->setCollection($mapped);

        return Inertia::render('Dua/Index', [
            'requests' => $requests,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // Rate limiting: max 3 requests per day
        $todayCount = DuaRequest::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayCount >= 3) {
            return back()->withErrors([
                'content' => 'You have reached the daily limit of 3 dua requests. Please try again tomorrow.',
            ])->withInput();
        }

        $data = $request->validate([
            'content' => ['required', 'string', 'min:10', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        DuaRequest::create([
            'user_id' => $user->id,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'content' => $data['content'],
            'status' => 'active',
        ]);

        return redirect()->route('dua.index')
            ->with('success', 'Your dua has been added to the wall.');
    }

    public function pray(Request $request, DuaRequest $dua)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        // Ensure request is active and not deleted
        if ($dua->status !== 'active' || $dua->trashed()) {
            abort(404);
        }

        $prayer = DuaPrayer::firstOrCreate([
            'dua_request_id' => $dua->id,
            'user_id' => $user->id,
        ]);

        // Log dua prayed (only if newly created)
        if ($prayer->wasRecentlyCreated) {
            $this->activityLogger->log(
                \App\Models\ActivityEvent::TYPE_DUA_PRAYED,
                $user,
                [
                    'subject' => $dua,
                ]
            );
        }

        // Refresh to get updated count
        $dua->refresh();
        $prayersCount = $dua->prayers()->count();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'prayers_count' => $prayersCount,
                'has_prayed' => true,
            ]);
        }

        return redirect()->route('dua.index')
            ->with('success', 'Your prayer was recorded.');
    }
}
