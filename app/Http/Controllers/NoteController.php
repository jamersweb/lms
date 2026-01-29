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
        
        $query = $user->notes()->with(['lesson', 'course'])->latest();
        
        // Filter by scope if provided
        if ($request->has('scope')) {
            $query->where('scope', $request->scope);
        }
        
        $notes = $query->get()->map(function($note) {
            $related = null;
            if ($note->lesson) {
                $related = $note->lesson->title;
            } elseif ($note->course) {
                $related = $note->course->title;
            }
            
            return [
                'id' => $note->id,
                'title' => $note->title,
                'content' => $note->content,
                'preview' => substr($note->content, 0, 100) . (strlen($note->content) > 100 ? '...' : ''),
                'scope' => $note->scope,
                'pinned' => $note->pinned,
                'updated_at' => $note->updated_at->diffForHumans(),
                'updated_at_raw' => $note->updated_at->toISOString(),
                'type' => $note->scope === 'lesson' ? 'Lesson' : ($note->scope === 'course' ? 'Course' : 'Personal'),
                'related' => $related,
                'lesson_id' => $note->lesson_id,
                'course_id' => $note->course_id,
            ];
        });

        return Inertia::render('Notes/Index', [
            'notes' => $notes,
            'filters' => $request->only(['scope'])
        ]);
    }

    /**
     * Store a newly created note.
     */
    public function store(StoreNoteRequest $request)
    {
        $data = $request->validated();
        
        // Map polymorphic to direct relationships if needed
        if (isset($data['noteable_type']) && isset($data['noteable_id'])) {
            if ($data['noteable_type'] === 'App\\Models\\Lesson') {
                $data['lesson_id'] = $data['noteable_id'];
                $data['scope'] = 'lesson';
            } elseif ($data['noteable_type'] === 'App\\Models\\Course') {
                $data['course_id'] = $data['noteable_id'];
                $data['scope'] = 'course';
            }
            unset($data['noteable_type'], $data['noteable_id']);
        }
        
        // Set default scope if not provided
        if (!isset($data['scope'])) {
            $data['scope'] = 'personal';
        }
        
        auth()->user()->notes()->create($data);

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
