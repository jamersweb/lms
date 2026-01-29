<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\User;
use App\Notifications\BroadcastNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class BroadcastController extends Controller
{
    public function index()
    {
        $broadcasts = Broadcast::latest()->limit(50)->get()->map(function (Broadcast $b) {
            return [
                'id' => $b->id,
                'subject' => $b->subject,
                'body' => $b->body,
                'audience' => $b->audience_json,
                'sent_at' => $b->sent_at?->toDateTimeString(),
            ];
        });

        return Inertia::render('Admin/Broadcasts/Index', [
            'broadcasts' => $broadcasts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'gender' => ['nullable', 'in:male,female'],
            'has_bayah' => ['nullable', 'boolean'],
            'level' => ['nullable', 'in:beginner,intermediate,expert'],
        ]);

        $filters = [
            'gender' => $data['gender'] ?? null,
            'has_bayah' => array_key_exists('has_bayah', $data) ? (bool) $data['has_bayah'] : null,
            'level' => $data['level'] ?? null,
        ];

        $query = User::query();

        if ($filters['gender']) {
            $query->where('gender', $filters['gender']);
        }

        if (! is_null($filters['has_bayah'])) {
            $query->where('has_bayah', $filters['has_bayah']);
        }

        if ($filters['level']) {
            $query->where('level', $filters['level']);
        }

        $users = $query->get();

        if ($users->isNotEmpty()) {
            Notification::send($users, new BroadcastNotification($data['subject'], $data['body']));
        }

        $broadcast = Broadcast::create([
            'audience_json' => $filters,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'sent_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $broadcast->id,
                'sent_to' => $users->pluck('id'),
            ]);
        }

        return redirect()->route('admin.broadcasts.index')
            ->with('success', 'Broadcast sent to '.$users->count().' users.');
    }
}
