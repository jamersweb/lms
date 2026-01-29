<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MicroHabitNudge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MicroHabitNudgeController extends Controller
{
    public function index()
    {
        $nudges = MicroHabitNudge::latest()->get()->map(function ($nudge) {
            return [
                'id' => $nudge->id,
                'title' => $nudge->title,
                'sunnah_topic' => $nudge->sunnah_topic,
                'send_at' => $nudge->send_at,
                'target_days' => $nudge->target_days,
                'is_active' => $nudge->is_active,
            ];
        });

        return Inertia::render('Admin/MicroHabitNudges/Index', [
            'nudges' => $nudges,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a', 'max:10240'],
            'sunnah_topic' => ['required', 'string', 'max:255'],
            'send_at' => ['required', 'date_format:H:i'],
            'target_days' => ['nullable', 'array'],
            'target_days.*' => ['integer', 'min:0', 'max:6'], // 0=Sunday, 6=Saturday
        ]);

        $audioFile = $request->file('audio');
        $path = $audioFile->store('micro-habit-nudges', 'public');

        MicroHabitNudge::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'audio_path' => $path,
            'duration_seconds' => 30, // Default for micro-nudges
            'sunnah_topic' => $validated['sunnah_topic'],
            'send_at' => $validated['send_at'],
            'target_days' => $validated['target_days'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.micro-habit-nudges.index')
            ->with('success', 'Micro-habit nudge created successfully.');
    }

    public function update(Request $request, MicroHabitNudge $nudge)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'audio' => ['sometimes', 'file', 'mimes:mp3,wav,m4a', 'max:10240'],
            'sunnah_topic' => ['required', 'string', 'max:255'],
            'send_at' => ['required', 'date_format:H:i'],
            'target_days' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        if ($request->hasFile('audio')) {
            // Delete old audio
            if ($nudge->audio_path) {
                Storage::disk('public')->delete($nudge->audio_path);
            }

            $audioFile = $request->file('audio');
            $validated['audio_path'] = $audioFile->store('micro-habit-nudges', 'public');
        }

        $nudge->update($validated);

        return redirect()->route('admin.micro-habit-nudges.index')
            ->with('success', 'Micro-habit nudge updated successfully.');
    }
}
