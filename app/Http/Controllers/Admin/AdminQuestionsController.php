<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionMessage;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminQuestionsController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $query = Question::with(['user', 'assignee', 'lastReplyBy'])
            ->withCount('messages');

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->assigned_to === 'me') {
            $query->where('assigned_to', Auth::id());
        } elseif ($request->assigned_to === 'unassigned') {
            $query->whereNull('assigned_to');
        }

        if ($request->context_type && $request->context_id) {
            $query->where('context_type', $request->context_type)
                  ->where('context_id', $request->context_id);
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('body', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($userQuery) use ($request) {
                      $userQuery->where('name', 'like', '%' . $request->search . '%')
                                ->orWhere('email', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Sort: open first, then by last_reply_at desc
        $questions = $query->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('last_reply_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function (Question $question) {
                return [
                    'id' => $question->id,
                    'title' => $question->title,
                    'status' => $question->status,
                    'priority' => $question->priority,
                    'user' => [
                        'id' => $question->user->id,
                        'name' => $question->user->name,
                        'email' => $question->user->email,
                    ],
                    'assignee' => $question->assignee ? [
                        'id' => $question->assignee->id,
                        'name' => $question->assignee->name,
                    ] : null,
                    'last_reply_at' => $question->last_reply_at?->diffForHumans(),
                    'created_at' => $question->created_at->diffForHumans(),
                    'messages_count' => $question->messages_count,
                ];
            });

        // Get admins for assignment dropdown
        $admins = User::where('is_admin', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Questions/Index', [
            'questions' => $questions,
            'admins' => $admins,
            'filters' => [
                'status' => $request->status,
                'assigned_to' => $request->assigned_to,
                'context_type' => $request->context_type,
                'context_id' => $request->context_id,
                'search' => $request->search,
            ],
        ]);
    }

    public function show(Request $request, Question $question)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $question->load(['user', 'assignee', 'lastReplyBy', 'context']);

        // Get all messages including internal ones for admin
        $messages = $question->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(function (QuestionMessage $message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_role' => $message->sender_role,
                    'is_internal' => $message->is_internal,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'audio_playable_url' => $message->audio_playable_url,
                    'created_at' => $message->created_at->diffForHumans(),
                    'read_at' => $message->read_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/Questions/Show', [
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'body' => $question->body,
                'status' => $question->status,
                'priority' => $question->priority,
                'user' => [
                    'id' => $question->user->id,
                    'name' => $question->user->name,
                    'email' => $question->user->email,
                ],
                'assignee' => $question->assignee ? [
                    'id' => $question->assignee->id,
                    'name' => $question->assignee->name,
                ] : null,
                'last_reply_at' => $question->last_reply_at?->toDateTimeString(),
                'created_at' => $question->created_at->diffForHumans(),
                'context' => $question->context ? [
                    'type' => $question->context_type,
                    'id' => $question->context_id,
                    'title' => $question->context->title ?? $question->context->name ?? null,
                ] : null,
            ],
            'messages' => $messages,
        ]);
    }

    public function message(Request $request, Question $question)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5'],
            'is_internal' => ['sometimes', 'boolean'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,m4a,wav', 'max:10240'], // 10MB max
            'audio_url' => ['nullable', 'url'],
        ]);

        $user = Auth::user();
        $audioType = null;
        $audioPath = null;
        $audioUrl = null;

        // Handle audio upload
        if ($request->hasFile('audio_file')) {
            $audioType = QuestionMessage::AUDIO_TYPE_UPLOAD;
            $audioPath = $request->file('audio_file')->store('ask-audio', 'public');
        } elseif ($validated['audio_url'] ?? null) {
            $audioType = QuestionMessage::AUDIO_TYPE_URL;
            $audioUrl = $validated['audio_url'];
        }

        $message = QuestionMessage::create([
            'question_id' => $question->id,
            'sender_id' => $user->id,
            'sender_role' => QuestionMessage::SENDER_ROLE_ADMIN,
            'message' => $validated['message'],
            'is_internal' => (bool) ($validated['is_internal'] ?? false),
            'audio_type' => $audioType,
            'audio_path' => $audioPath,
            'audio_url' => $audioUrl,
        ]);

        // Update question
        $updates = [
            'last_reply_at' => now(),
            'last_reply_by' => $user->id,
        ];

        // Auto-set status to answered if not internal
        if (!($validated['is_internal'] ?? false) && $question->status === Question::STATUS_OPEN) {
            $updates['status'] = Question::STATUS_ANSWERED;
        }

        $question->update($updates);

        // Log question reply
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_QUESTION_REPLIED,
            $user,
            [
                'subject' => $question,
            ]
        );

        // Notify student if not internal
        if (!($validated['is_internal'] ?? false)) {
            $question->user->notify(new \App\Notifications\QuestionReplyNotification($question, $user));
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function update(Request $request, Question $question)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:open,answered,resolved'],
            'priority' => ['sometimes', 'string', 'in:low,normal,high'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $updates = [];

        if (isset($validated['status'])) {
            $updates['status'] = $validated['status'];
            if ($validated['status'] === Question::STATUS_RESOLVED) {
                $updates['closed_at'] = now();
            } elseif ($question->closed_at) {
                $updates['closed_at'] = null; // Reopen
            }
        }

        if (isset($validated['priority'])) {
            $updates['priority'] = $validated['priority'];
        }

        if (isset($validated['assigned_to'])) {
            $updates['assigned_to'] = $validated['assigned_to'];
        }

        $question->update($updates);

        return back()->with('success', 'Question updated successfully.');
    }
}
