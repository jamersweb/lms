<?php

namespace App\Http\Controllers;

use App\Models\UserVoiceNote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserVoiceNoteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $voiceNotes = UserVoiceNote::where('user_id', $user->id)
            ->with('creator')
            ->latest()
            ->paginate(20);

        $voiceNotes->getCollection()->transform(function (UserVoiceNote $note) {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'note' => $note->note,
                'audio_url' => $note->audio_url,
                'created_by' => [
                    'id' => $note->creator->id,
                    'name' => $note->creator->name,
                ],
                'created_at' => $note->created_at->diffForHumans(),
            ];
        });

        return Inertia::render('VoiceNotes/Index', [
            'voiceNotes' => $voiceNotes,
        ]);
    }
}
