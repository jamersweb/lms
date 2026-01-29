<?php

namespace App\Http\Controllers;

use App\Models\AskMessage;
use App\Models\AskThread;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AskThreadController extends Controller
{
    public function index(Request $request)
    {
        $threads = AskThread::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        $threads->getCollection()->transform(function (AskThread $thread) {
            return [
                'id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'created_at' => $thread->created_at->diffForHumans(),
            ];
        });

        return Inertia::render('Ask/Index', [
            'threads' => $threads,
        ]);
    }

    public function create()
    {
        return Inertia::render('Ask/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $thread = AskThread::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'],
            'status' => 'open',
        ]);

        AskMessage::create([
            'ask_thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'sender_type' => 'user',
            'body' => $validated['body'],
        ]);

        return redirect()->route('ask.show', $thread);
    }

    public function show(Request $request, AskThread $thread)
    {
        abort_unless($thread->user_id === $request->user()->id || $request->user()->is_admin, 403);

        $thread->load('user');

        $messages = $thread->messages()->with('user')->orderBy('created_at')->get()->map(function (AskMessage $message) {
            return [
                'id' => $message->id,
                'body' => $message->body,
                'sender_type' => $message->sender_type,
                'created_at' => $message->created_at->diffForHumans(),
                'user' => [
                    'name' => $message->user->name,
                    'is_admin' => $message->user->is_admin,
                ],
            ];
        });

        return Inertia::render('Ask/Show', [
            'thread' => [
                'id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'created_at' => $thread->created_at->diffForHumans(),
            ],
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, AskThread $thread)
    {
        abort_unless($thread->user_id === $request->user()->id || $request->user()->is_admin, 403);

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        AskMessage::create([
            'ask_thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'sender_type' => $request->user()->is_admin ? 'mentor' : 'user',
            'body' => $validated['body'],
        ]);

        return back();
    }
}
