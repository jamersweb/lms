<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class StudentQuestionsController extends Controller
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}
    public function index(Request $request)
    {
        $user = $request->user();

        $questions = Question::where('user_id', $user->id)
            ->with(['lastReplyBy', 'assignee'])
            ->withCount('messages')
            ->latest()
            ->paginate(15);

        $questions->getCollection()->transform(function (Question $question) {
            return [
                'id' => $question->id,
                'title' => $question->title,
                'status' => $question->status,
                'priority' => $question->priority,
                'last_reply_at' => $question->last_reply_at?->diffForHumans(),
                'created_at' => $question->created_at->diffForHumans(),
                'messages_count' => $question->messages_count,
                'assignee' => $question->assignee ? [
                    'id' => $question->assignee->id,
                    'name' => $question->assignee->name,
                ] : null,
            ];
        });

        return Inertia::render('Questions/Index', [
            'questions' => $questions,
        ]);
    }

    public function create(Request $request)
    {
        // Optional: Load courses/modules/lessons for context selection
        $courses = \App\Models\Course::select('id', 'title')->orderBy('title')->get();

        return Inertia::render('Questions/Create', [
            'courses' => $courses,
            'context_type' => $request->input('context_type'),
            'context_id' => $request->input('context_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'min:10'],
            'context_type' => ['nullable', 'string', 'in:course,module,lesson'],
            'context_id' => ['nullable', 'integer'],
            'priority' => ['nullable', 'string', 'in:low,normal,high'],
        ]);

        $user = $request->user();

        // Rate limiting: max 5 questions per hour
        $recentCount = Question::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        if ($recentCount >= 5) {
            return back()->withErrors([
                'title' => 'You have reached the hourly limit of 5 questions. Please try again later.',
            ])->withInput();
        }

        $question = Question::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'body' => $validated['body'],
            'status' => Question::STATUS_OPEN,
            'priority' => $validated['priority'] ?? Question::PRIORITY_NORMAL,
            'context_type' => $validated['context_type'] ?? null,
            'context_id' => $validated['context_id'] ?? null,
        ]);

        // Create initial message
        QuestionMessage::create([
            'question_id' => $question->id,
            'sender_id' => $user->id,
            'sender_role' => QuestionMessage::SENDER_ROLE_STUDENT,
            'message' => $validated['body'],
        ]);

        // Log question creation
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_QUESTION_CREATED,
            $user,
            [
                'subject' => $question,
                'course_id' => $validated['context_type'] === 'course' ? $validated['context_id'] : null,
                'module_id' => $validated['context_type'] === 'module' ? $validated['context_id'] : null,
                'lesson_id' => $validated['context_type'] === 'lesson' ? $validated['context_id'] : null,
            ]
        );

        // Notify teachers/admins (handled by notification)
        $this->notifyTeachers($question);

        return redirect()->route('questions.show', $question)
            ->with('success', 'Your question has been submitted.');
    }

    public function show(Request $request, Question $question)
    {
        // Ensure student can only view their own questions
        abort_unless($question->user_id === $request->user()->id, 403);

        $question->load(['user', 'assignee', 'lastReplyBy', 'context']);

        // Get messages excluding internal ones for students
        $messages = $question->messages()
            ->with('sender')
            ->where('is_internal', false)
            ->orderBy('created_at')
            ->get()
            ->map(function (QuestionMessage $message) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_role' => $message->sender_role,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'audio_playable_url' => $message->audio_playable_url,
                    'created_at' => $message->created_at->diffForHumans(),
                    'read_at' => $message->read_at?->toDateTimeString(),
                ];
            });

        // Mark messages as read when student views
        $question->messages()
            ->where('sender_role', '!=', QuestionMessage::SENDER_ROLE_STUDENT)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Inertia::render('Questions/Show', [
            'question' => [
                'id' => $question->id,
                'title' => $question->title,
                'body' => $question->body,
                'status' => $question->status,
                'priority' => $question->priority,
                'created_at' => $question->created_at->diffForHumans(),
                'assignee' => $question->assignee ? [
                    'id' => $question->assignee->id,
                    'name' => $question->assignee->name,
                ] : null,
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
        // Ensure student can only message their own questions
        abort_unless($question->user_id === $request->user()->id, 403);

        // Prevent messages if resolved (unless you want to allow reopening)
        if ($question->status === Question::STATUS_RESOLVED) {
            return back()->withErrors([
                'message' => 'This question has been resolved. Please create a new question if you need further assistance.',
            ]);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5'],
        ]);

        // Rate limiting: max 10 messages per hour
        $recentCount = QuestionMessage::where('sender_id', $request->user()->id)
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->count();

        if ($recentCount >= 10) {
            return back()->withErrors([
                'message' => 'You have reached the hourly limit of 10 messages. Please try again later.',
            ]);
        }

        QuestionMessage::create([
            'question_id' => $question->id,
            'sender_id' => $request->user()->id,
            'sender_role' => QuestionMessage::SENDER_ROLE_STUDENT,
            'message' => $validated['message'],
        ]);

        // Update question last_reply_at
        $question->update([
            'last_reply_at' => now(),
            'last_reply_by' => $request->user()->id,
        ]);

        // Log question reply
        $this->activityLogger->log(
            \App\Models\ActivityEvent::TYPE_QUESTION_REPLIED,
            $request->user(),
            [
                'subject' => $question,
            ]
        );

        // Notify assigned teacher/admin
        if ($question->assigned_to) {
            $this->notifyAssignee($question, $request->user());
        }

        return back()->with('success', 'Your message has been sent.');
    }

    public function markResolved(Request $request, Question $question)
    {
        // Ensure student can only mark their own questions as resolved
        abort_unless($question->user_id === $request->user()->id, 403);

        $question->update([
            'status' => Question::STATUS_RESOLVED,
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Question marked as resolved.');
    }

    protected function notifyTeachers(Question $question)
    {
        // Notify all admins (or configured support inbox user)
        $admins = \App\Models\User::where('is_admin', true)->get();

        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewQuestionNotification($question));
        }
    }

    protected function notifyAssignee(Question $question, User $student)
    {
        if ($question->assigned_to) {
            $assignee = \App\Models\User::find($question->assigned_to);
            if ($assignee) {
                $assignee->notify(new \App\Notifications\QuestionReplyNotification($question, $student));
            }
        }
    }
}
