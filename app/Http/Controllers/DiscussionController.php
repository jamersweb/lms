<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscussionRequest;
use App\Models\Course;
use App\Models\Discussion;
use App\Services\PointsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiscussionController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display discussions for a course.
     */
    public function index(Request $request, Course $course)
    {
        $this->authorize('viewAny', Discussion::class);
        $discussions = $course->discussions()
            ->with('user')
            ->withCount('replies')
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->paginate(15);
        
        $discussions->getCollection()->transform(function($discussion) {
            return [
                'id' => $discussion->id,
                'title' => $discussion->title,
                'body' => substr($discussion->body, 0, 200),
                'user' => [
                    'name' => $discussion->user->name,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($discussion->user->name) . '&background=random'
                ],
                'replies_count' => $discussion->replies_count,
                'is_pinned' => $discussion->is_pinned,
                'created_at' => $discussion->created_at->diffForHumans(),
                'course_id' => $discussion->course_id
            ];
        });

        return Inertia::render('Discussions/Index', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title
            ],
            'discussions' => $discussions,
            'filters' => $request->only(['lesson_id']),
        ]);
    }

    /**
     * Display a specific discussion with replies.
     */
    public function show(Discussion $discussion)
    {
        $this->authorize('view', $discussion);
        
        $discussion->load(['user', 'replies.user', 'course']);
        
        $formattedReplies = $discussion->replies->map(function($reply) {
            return [
                'id' => $reply->id,
                'body' => $reply->body,
                'user' => [
                    'name' => $reply->user->name,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($reply->user->name) . '&background=random',
                    'is_instructor' => $reply->user->is_admin ?? false
                ],
                'created_at' => $reply->created_at->diffForHumans()
            ];
        });

        return Inertia::render('Discussions/Show', [
            'discussion' => [
                'id' => $discussion->id,
                'title' => $discussion->title,
                'body' => $discussion->body,
                'user' => [
                    'name' => $discussion->user->name,
                    'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($discussion->user->name) . '&background=random'
                ],
                'created_at' => $discussion->created_at->diffForHumans(),
                'course' => [
                    'id' => $discussion->course->id,
                    'title' => $discussion->course->title
                ]
            ],
            'replies' => $formattedReplies
        ]);
    }

    /**
     * Store a new discussion.
     */
    public function store(StoreDiscussionRequest $request, Course $course)
    {
        $validated = $request->validated();
        
        $discussion = $course->discussions()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'body' => $validated['body']
        ]);

        // Award points
        PointsService::award(auth()->user(), 'discussion_created', 2);

        return redirect()->route('discussions.show', $discussion)
            ->with('success', 'Discussion created successfully! +2 points');
    }
}
