<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioClip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AudioClipController extends Controller
{
    /**
     * Display a listing of audio clips.
     */
    public function index()
    {
        $clips = AudioClip::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/MicroNudges/Clips/Index', [
            'clips' => $clips->map(function ($clip) {
                return [
                    'id' => $clip->id,
                    'title' => $clip->title,
                    'description' => $clip->description,
                    'source_type' => $clip->source_type,
                    'file_path' => $clip->file_path,
                    'external_url' => $clip->external_url,
                    'duration_seconds' => $clip->duration_seconds,
                    'is_active' => $clip->is_active,
                    'playable_url' => $clip->playable_url,
                    'created_at' => $clip->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created audio clip.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'source_type' => 'required|in:upload,url',
            'audio_file' => 'required_if:source_type,upload|file|mimes:mp3,mp4,m4a,ogg,wav|max:10240', // 10MB
            'external_url' => 'required_if:source_type,url|url|max:500',
            'duration_seconds' => 'nullable|integer|min:1|max:3600',
            'is_active' => 'boolean',
        ]);

        $clip = new AudioClip();
        $clip->title = $validated['title'];
        $clip->description = $validated['description'] ?? null;
        $clip->source_type = $validated['source_type'];
        $clip->duration_seconds = $validated['duration_seconds'] ?? null;
        $clip->is_active = $validated['is_active'] ?? true;

        if ($validated['source_type'] === 'upload' && $request->hasFile('audio_file')) {
            $path = $request->file('audio_file')->store('micro-nudges', 'public');
            $clip->file_path = $path;
        } elseif ($validated['source_type'] === 'url') {
            $clip->external_url = $validated['external_url'];
        }

        $clip->save();

        return redirect()->route('admin.micro-nudges.clips.index')
            ->with('success', 'Audio clip created successfully.');
    }

    /**
     * Update the specified audio clip.
     */
    public function update(Request $request, AudioClip $clip)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'source_type' => 'required|in:upload,url',
            'audio_file' => 'nullable|file|mimes:mp3,mp4,m4a,ogg,wav|max:10240',
            'external_url' => 'required_if:source_type,url|nullable|url|max:500',
            'duration_seconds' => 'nullable|integer|min:1|max:3600',
            'is_active' => 'boolean',
        ]);

        $clip->title = $validated['title'];
        $clip->description = $validated['description'] ?? null;
        $clip->source_type = $validated['source_type'];
        $clip->duration_seconds = $validated['duration_seconds'] ?? null;
        $clip->is_active = $validated['is_active'] ?? true;

        // Handle file upload
        if ($validated['source_type'] === 'upload') {
            if ($request->hasFile('audio_file')) {
                // Delete old file if exists
                if ($clip->file_path && Storage::disk('public')->exists($clip->file_path)) {
                    Storage::disk('public')->delete($clip->file_path);
                }

                $path = $request->file('audio_file')->store('micro-nudges', 'public');
                $clip->file_path = $path;
            } elseif (!$clip->file_path) {
                return back()->withErrors(['audio_file' => 'Audio file is required for upload type.']);
            }
        } else {
            // URL type
            $clip->file_path = null;
            $clip->external_url = $validated['external_url'] ?? null;
        }

        $clip->save();

        return redirect()->route('admin.micro-nudges.clips.index')
            ->with('success', 'Audio clip updated successfully.');
    }

    /**
     * Remove the specified audio clip.
     */
    public function destroy(AudioClip $clip)
    {
        // Delete file if exists
        if ($clip->file_path && Storage::disk('public')->exists($clip->file_path)) {
            Storage::disk('public')->delete($clip->file_path);
        }

        $clip->delete();

        return redirect()->route('admin.micro-nudges.clips.index')
            ->with('success', 'Audio clip deleted successfully.');
    }
}
