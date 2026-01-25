<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscussionReplyRequest;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Services\PointsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class DiscussionReplyController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a new reply to a discussion.
     */
    public function store(StoreDiscussionReplyRequest $request, Discussion $discussion)
    {
        $validated = $request->validated();

        $reply = $discussion->replies()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body']
        ]);

        // Award points
        PointsService::award(auth()->user(), 'reply_created', 1);

        return back()->with('success', 'Reply posted successfully! +1 point');
    }
}
