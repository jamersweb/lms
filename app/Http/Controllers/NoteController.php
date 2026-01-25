<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class NoteController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of user's notes.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $query = $user->notes()->with('noteable')->latest();
        
        // Filter by scope if provided
        if ($request->has('scope')) {
            $query->where('scope', $request->scope);
        }
        
        $notes = $query->get()->map(function($note) {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'preview' => substr($note->content, 0, 100) . '...',
                'updated_at' => $note->updated_at->diffForHumans(),
                'type' => class_basename($note->noteable_type),
                'related' => $note->noteable ? $note->noteable->title : null
            ];
        });

        return Inertia::render('Notes/Index', [
            'notes' => $notes
        ]);
    }

    /**
     * Store a newly created note.
     */
    public function store(StoreNoteRequest $request)
    {
        auth()->user()->notes()->create($request->validated());

        return redirect()->route('notes.index')
            ->with('success', 'Note created successfully!');
    }

    /**
     * Update the specified note.
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        $note->update($request->validated());

        return redirect()->route('notes.index')
            ->with('success', 'Note updated successfully!');
    }

    /**
     * Remove the specified note.
     */
    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);
        
        $note->delete();

        return redirect()->route('notes.index')
            ->with('success', 'Note deleted successfully!');
    }
}
