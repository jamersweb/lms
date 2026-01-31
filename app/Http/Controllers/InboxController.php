<?php

namespace App\Http\Controllers;

use App\Models\Broadcast;
use App\Notifications\BroadcastInAppNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InboxController extends Controller
{
    /**
     * Display the user's inbox.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get broadcasts that were delivered to this user (via in-app channel)
        $broadcastIds = $user->notifications()
            ->where('type', BroadcastInAppNotification::class)
            ->get()
            ->pluck('data.broadcast_id')
            ->filter()
            ->unique();

        // Also get from broadcast_deliveries for in-app channel
        $deliveredBroadcastIds = \App\Models\BroadcastDelivery::where('user_id', $user->id)
            ->where('channel', Broadcast::CHANNEL_IN_APP)
            ->where('status', \App\Models\BroadcastDelivery::STATUS_SENT)
            ->pluck('broadcast_id')
            ->unique();

        $allBroadcastIds = $broadcastIds->merge($deliveredBroadcastIds)->unique();

        $broadcasts = Broadcast::whereIn('id', $allBroadcastIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($broadcast) use ($user) {
                $notification = $user->notifications()
                    ->where('type', BroadcastInAppNotification::class)
                    ->get()
                    ->first(function ($n) use ($broadcast) {
                        return isset($n->data['broadcast_id']) && $n->data['broadcast_id'] == $broadcast->id;
                    });

                return [
                    'id' => $broadcast->id,
                    'title' => $broadcast->title,
                    'body' => $broadcast->body,
                    'created_at' => $broadcast->created_at->toIso8601String(),
                    'read_at' => $notification?->read_at?->toIso8601String(),
                    'is_read' => $notification ? $notification->read_at !== null : false,
                ];
            });

        return Inertia::render('Inbox/Index', [
            'broadcasts' => $broadcasts,
        ]);
    }

    /**
     * Display a specific broadcast.
     */
    public function show(Request $request, Broadcast $broadcast)
    {
        $user = $request->user();

        // Check if user has access to this broadcast
        $hasAccess = \App\Models\BroadcastDelivery::where('broadcast_id', $broadcast->id)
            ->where('user_id', $user->id)
            ->where('channel', Broadcast::CHANNEL_IN_APP)
            ->where('status', \App\Models\BroadcastDelivery::STATUS_SENT)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'You do not have access to this broadcast.');
        }

        // Mark notification as read
        $notification = $user->notifications()
            ->where('type', BroadcastInAppNotification::class)
            ->get()
            ->first(function ($n) use ($broadcast) {
                return isset($n->data['broadcast_id']) && $n->data['broadcast_id'] == $broadcast->id;
            });

        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }

        return Inertia::render('Inbox/Show', [
            'broadcast' => [
                'id' => $broadcast->id,
                'title' => $broadcast->title,
                'body' => $broadcast->body,
                'created_at' => $broadcast->created_at->toIso8601String(),
            ],
        ]);
    }
}
