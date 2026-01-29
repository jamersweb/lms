<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AskMessage;
use App\Models\AskThread;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AskThreadController extends Controller
{
    public function index(Request $request)
    {
        $threads = AskThread::with('user')->latest()->paginate(20);

        $threads->getCollection()->transform(function (AskThread $thread) {
            return [
                'id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'created_at' => $thread->created_at->diffForHumans(),
                'user' => [
                    'id' => $thread->user->id,
                    'name' => $thread->user->name,
                ],
            ];
        });

        return Inertia::render('Admin/Ask/Index', [
            'threads' => $threads,
        ]);
    }

    public function show(Request $request, AskThread $thread)
    {
        $thread->load('user');

        $messages = $thread->messages()->with('user')->orderBy('created_at')->get()->map(function (AskMessage $message) {
            return [
                'id' => $message->id,
                'body' => $message->body,
                'sender_type' => $message->sender_type,
                'created_at' => $message->created_at->diffForHumans(),
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'is_admin' => $message->user->is_admin,
                ],
            ];
        });

        return Inertia::render('Admin/Ask/Show', [
            'thread' => [
                'id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'created_at' => $thread->created_at->diffForHumans(),
                'user' => [
                    'id' => $thread->user->id,
                    'name' => $thread->user->name,
                ],
            ],
            'messages' => $messages,
        ]);
    }

    public function reply(Request $request, AskThread $thread)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        AskMessage::create([
            'ask_thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'sender_type' => 'mentor',
            'body' => $validated['body'],
        ]);

        return back();
    }

    public function close(Request $request, AskThread $thread)
    {
        $thread->update(['status' => 'closed']);

        return back();
    }
}
