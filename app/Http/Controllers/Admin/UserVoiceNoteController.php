<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserVoiceNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserVoiceNoteController extends Controller
{
    public function store(Request $request, User $user)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,m4a,wav', 'max:10240'], // 10MB max
            'audio_url' => ['nullable', 'url'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $audioType = null;
        $audioPath = null;
        $audioUrl = null;

        // Handle audio upload
        if ($request->hasFile('audio_file')) {
            $audioType = UserVoiceNote::AUDIO_TYPE_UPLOAD;
            $audioPath = $request->file('audio_file')->store('voice-notes', 'public');
        } elseif ($validated['audio_url'] ?? null) {
            $audioType = UserVoiceNote::AUDIO_TYPE_URL;
            $audioUrl = $validated['audio_url'];
        } else {
            return back()->withErrors([
                'audio_file' => 'Either audio file or audio URL is required.',
            ]);
        }

        UserVoiceNote::create([
            'user_id' => $user->id,
            'created_by' => Auth::id(),
            'title' => $validated['title'] ?? null,
            'note' => $validated['note'] ?? null,
            'audio_type' => $audioType,
            'audio_path' => $audioPath,
            'audio_url' => $audioUrl,
            'is_private' => (bool) ($validated['is_private'] ?? true),
        ]);

        return back()->with('success', 'Voice note sent successfully.');
    }
}
