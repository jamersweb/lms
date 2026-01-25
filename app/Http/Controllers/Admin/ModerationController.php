<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\ModerationAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ModerationController extends Controller
{
    public function index()
    {
        if (!Auth::user()->is_admin) abort(403);

        $actions = ModerationAction::with('moderator')->latest()->limit(20)->get();
        
        $discussions = Discussion::withTrashed()->with('user')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Moderation/Index', [
            'actions' => $actions,
            'discussions' => $discussions,
        ]);
    }

    public function handle(Request $request)
    {
        if (!Auth::user()->is_admin) abort(403);

        $validated = $request->validate([
            'target_type' => 'required|in:discussion,reply',
            'target_id' => 'required|integer',
            'action' => 'required|in:lock,unlock,delete,restore',
            'reason' => 'nullable|string',
        ]);

        $model = match($validated['target_type']) {
            'discussion' => Discussion::withTrashed()->find($validated['target_id']),
            'reply' => DiscussionReply::withTrashed()->find($validated['target_id']),
        };

        if (!$model) abort(404);

        switch ($validated['action']) {
            case 'lock':
                if (method_exists($model, 'update')) $model->update(['status' => 'closed']);
                break;
            case 'unlock':
                if (method_exists($model, 'update')) $model->update(['status' => 'open']);
                break;
            case 'delete':
                $model->delete();
                break;
            case 'restore':
                $model->restore();
                break;
        }

        // Log action
        ModerationAction::create([
            'moderator_id' => Auth::id(),
            'target_type' => $validated['target_type'],
            'target_id' => $validated['target_id'],
            'action' => $validated['action'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return redirect()->back();
    }
}
