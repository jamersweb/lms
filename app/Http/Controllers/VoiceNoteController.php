<?php

namespace App\Http\Controllers;

use App\Models\AskThread;
use App\Models\VoiceNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VoiceNoteController extends Controller
{
    public function store(Request $request, AskThread $thread)
    {
        $user = $request->user();
        
        // Only admins/mentors can send voice notes
        abort_unless($user->is_admin, 403);

        $request->validate([
            'audio' => ['required', 'file', 'mimes:mp3,wav,m4a', 'max:10240'], // 10MB max
        ]);

        $audioFile = $request->file('audio');
        $path = $audioFile->store('voice-notes', 'public');

        $voiceNote = VoiceNote::create([
            'ask_thread_id' => $thread->id,
            'sender_id' => $user->id,
            'audio_path' => $path,
            'duration_seconds' => null, // Could extract from audio file if needed
        ]);

        return response()->json([
            'id' => $voiceNote->id,
            'audio_url' => $voiceNote->audio_url,
            'created_at' => $voiceNote->created_at->diffForHumans(),
        ]);
    }

    public function destroy(VoiceNote $voiceNote)
    {
        $user = auth()->user();

        abort_unless($user->is_admin || $voiceNote->sender_id === $user->id, 403);

        if ($voiceNote->audio_path) {
            Storage::disk('public')->delete($voiceNote->audio_path);
        }

        $voiceNote->delete();

        return response()->json(['ok' => true]);
    }
}
