<?php

namespace App\Http\Controllers;

use App\Models\DuaPrayer;
use App\Models\DuaRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DuaWallController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DuaRequest::query()
            ->with('user')
            ->withCount('prayers')
            ->latest();

        if ($user) {
            $query->with(['prayers' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        }

        $requests = $query->paginate(20);

        $mapped = $requests->getCollection()->map(function (DuaRequest $dua) use ($user) {
            $hasPrayed = $user
                ? $dua->prayers->where('user_id', $user->id)->isNotEmpty()
                : false;

            return [
                'id' => $dua->id,
                'request_text' => $dua->request_text,
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
        $data = $request->validate([
            'request_text' => ['required', 'string', 'max:1000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        DuaRequest::create([
            'user_id' => $user?->id,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'request_text' => $data['request_text'],
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

        DuaPrayer::firstOrCreate([
            'dua_request_id' => $dua->id,
            'user_id' => $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('dua.index');
    }
}
