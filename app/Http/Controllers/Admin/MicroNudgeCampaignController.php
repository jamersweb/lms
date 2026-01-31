<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioClip;
use App\Models\MicroNudgeCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MicroNudgeCampaignController extends Controller
{
    /**
     * Display a listing of campaigns.
     */
    public function index()
    {
        $campaigns = MicroNudgeCampaign::withCount('deliveries')->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/MicroNudges/Campaigns/Index', [
            'campaigns' => $campaigns->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'is_enabled' => $campaign->is_enabled,
                    'schedule_type' => $campaign->schedule_type,
                    'send_hour' => $campaign->send_hour,
                    'send_minute' => $campaign->send_minute,
                    'rotation' => $campaign->rotation,
                    'audience_filters' => $campaign->audience_filters,
                    'clip_ids' => $campaign->clip_ids,
                    'deliveries_count' => $campaign->deliveries_count,
                    'created_at' => $campaign->created_at->toIso8601String(),
                ];
            }),
            'audioClips' => AudioClip::where('is_active', true)->get(['id', 'title']),
        ]);
    }

    /**
     * Show the form for creating a new campaign.
     */
    public function create()
    {
        return Inertia::render('Admin/MicroNudges/Campaigns/Edit', [
            'campaign' => null,
            'audioClips' => AudioClip::where('is_active', true)->get(['id', 'title', 'duration_seconds']),
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $validated = $this->validateCampaign($request);

        $campaign = MicroNudgeCampaign::create($validated);

        return redirect()->route('admin.micro-nudges.campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    /**
     * Show the form for editing the specified campaign.
     */
    public function edit(MicroNudgeCampaign $campaign)
    {
        return Inertia::render('Admin/MicroNudges/Campaigns/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'is_enabled' => $campaign->is_enabled,
                'schedule_type' => $campaign->schedule_type,
                'send_hour' => $campaign->send_hour,
                'send_minute' => $campaign->send_minute,
                'timezone' => $campaign->timezone,
                'rotation' => $campaign->rotation,
                'audience_filters' => $campaign->audience_filters ?? [],
                'clip_ids' => $campaign->clip_ids ?? [],
            ],
            'audioClips' => AudioClip::where('is_active', true)->get(['id', 'title', 'duration_seconds']),
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, MicroNudgeCampaign $campaign)
    {
        $validated = $this->validateCampaign($request);

        $campaign->update($validated);

        return redirect()->route('admin.micro-nudges.campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(MicroNudgeCampaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.micro-nudges.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    /**
     * Validate campaign data.
     */
    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'is_enabled' => 'boolean',
            'schedule_type' => 'required|in:hourly,daily',
            'send_hour' => 'required_if:schedule_type,daily|nullable|integer|min:0|max:23',
            'send_minute' => 'required|integer|min:0|max:59',
            'timezone' => 'nullable|string|max:50',
            'rotation' => 'required|in:random,sequence',
            'audience_filters' => 'nullable|array',
            'audience_filters.min_level' => 'nullable|in:beginner,intermediate,expert',
            'audience_filters.requires_bayah' => 'nullable|boolean',
            'audience_filters.gender' => 'nullable|in:male,female',
            'clip_ids' => 'nullable|array',
            'clip_ids.*' => 'exists:audio_clips,id',
        ]);
    }
}
