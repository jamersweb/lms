<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\Course;
use App\Jobs\SendBroadcastJob;
use App\Services\BroadcastAudienceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BroadcastController extends Controller
{
    /**
     * Display a listing of broadcasts.
     */
    public function index()
    {
        $broadcasts = Broadcast::with('creator')
            ->withCount('deliveries')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($broadcast) {
                return [
                    'id' => $broadcast->id,
                    'title' => $broadcast->title,
                    'status' => $broadcast->status,
                    'channels' => $broadcast->channels,
                    'audience_filters' => $broadcast->audience_filters,
                    'deliveries_count' => $broadcast->deliveries_count,
                    'created_by' => $broadcast->creator->name ?? 'Unknown',
                    'created_at' => $broadcast->created_at->toIso8601String(),
                    'sent_at' => $broadcast->sent_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Admin/Broadcasts/Index', [
            'broadcasts' => $broadcasts,
        ]);
    }

    /**
     * Show the form for creating a new broadcast.
     */
    public function create()
    {
        return Inertia::render('Admin/Broadcasts/Create', [
            'courses' => Course::all(['id', 'title']),
        ]);
    }

    /**
     * Store a newly created broadcast (draft).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,whatsapp,in_app',
            'audience_filters' => 'nullable|array',
            'audience_filters.min_level' => 'nullable|in:beginner,intermediate,expert',
            'audience_filters.requires_bayah' => 'nullable|boolean',
            'audience_filters.gender' => 'nullable|in:male,female',
            'audience_filters.course_id' => 'nullable|exists:courses,id',
        ]);

        $broadcast = Broadcast::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'channels' => $validated['channels'],
            'audience_filters' => $validated['audience_filters'] ?? [],
            'status' => Broadcast::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.broadcasts.show', $broadcast)
            ->with('success', 'Broadcast created as draft.');
    }

    /**
     * Display the specified broadcast.
     */
    public function show(Broadcast $broadcast)
    {
        $broadcast->load('creator', 'deliveries.user');

        $deliveryStats = $broadcast->deliveries()
            ->selectRaw('channel, status, COUNT(*) as count')
            ->groupBy('channel', 'status')
            ->get()
            ->groupBy('channel')
            ->map(function ($group) {
                return $group->pluck('count', 'status');
            });

        return Inertia::render('Admin/Broadcasts/Show', [
            'broadcast' => [
                'id' => $broadcast->id,
                'title' => $broadcast->title,
                'body' => $broadcast->body,
                'channels' => $broadcast->channels,
                'audience_filters' => $broadcast->audience_filters,
                'status' => $broadcast->status,
                'created_by' => $broadcast->creator->name ?? 'Unknown',
                'created_at' => $broadcast->created_at->toIso8601String(),
                'sent_at' => $broadcast->sent_at?->toIso8601String(),
                'delivery_stats' => $deliveryStats,
            ],
            'courses' => Course::all(['id', 'title']),
        ]);
    }

    /**
     * Update the specified broadcast (draft only).
     */
    public function update(Request $request, Broadcast $broadcast)
    {
        if (!$broadcast->isDraft()) {
            return back()->withErrors(['error' => 'Only draft broadcasts can be edited.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,whatsapp,in_app',
            'audience_filters' => 'nullable|array',
            'audience_filters.min_level' => 'nullable|in:beginner,intermediate,expert',
            'audience_filters.requires_bayah' => 'nullable|boolean',
            'audience_filters.gender' => 'nullable|in:male,female',
            'audience_filters.course_id' => 'nullable|exists:courses,id',
        ]);

        $broadcast->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'channels' => $validated['channels'],
            'audience_filters' => $validated['audience_filters'] ?? [],
        ]);

        return redirect()->route('admin.broadcasts.show', $broadcast)
            ->with('success', 'Broadcast updated.');
    }

    /**
     * Preview audience for a broadcast.
     */
    public function preview(Request $request, Broadcast $broadcast = null)
    {
        $filters = $request->input('audience_filters', $broadcast?->audience_filters ?? []);
        $channels = $request->input('channels', $broadcast?->channels ?? []);

        $audienceService = app(BroadcastAudienceService::class);

        // Count total matching users
        $totalCount = $audienceService->count($filters);

        // Count per channel (with opt-ins)
        $channelCounts = [];
        foreach ($channels as $channel) {
            $query = $audienceService->query($filters);
            $audienceService->applyChannelOptIn($query, $channel);
            $channelCounts[$channel] = $query->count();
        }

        // Get sample users (max 20)
        $sample = $audienceService->sample($filters, 20)->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'level' => $user->level,
                'gender' => $user->gender,
                'has_bayah' => $user->has_bayah,
            ];
        });

        return response()->json([
            'total_count' => $totalCount,
            'channel_counts' => $channelCounts,
            'sample' => $sample,
        ]);
    }

    /**
     * Send the broadcast (queue job).
     */
    public function send(Broadcast $broadcast)
    {
        if (!$broadcast->isDraft()) {
            return back()->withErrors(['error' => 'Only draft broadcasts can be sent.']);
        }

        // Dispatch job
        SendBroadcastJob::dispatch($broadcast->id);

        return redirect()->route('admin.broadcasts.show', $broadcast)
            ->with('success', 'Broadcast queued for sending.');
    }
}
