<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DuaRequest;
use App\Models\ModerationAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DuaWallController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $query = DuaRequest::withTrashed()
            ->with(['user', 'hiddenBy'])
            ->withCount('prayers');

        // Filters
        if ($request->status === 'active') {
            $query->where('status', 'active');
        } elseif ($request->status === 'hidden') {
            $query->where('status', 'hidden');
        }

        if ($request->deleted === 'yes') {
            $query->onlyTrashed();
        } elseif ($request->deleted === 'no') {
            $query->whereNull('deleted_at');
        }

        // Search
        if ($request->search) {
            $query->where('content', 'like', '%' . $request->search . '%');
        }

        $requests = $query->latest()->paginate(20)->through(function ($dua) {
            return [
                'id' => $dua->id,
                'content' => $dua->content,
                'content_snippet' => \Str::limit($dua->content, 100),
                'is_anonymous' => $dua->is_anonymous,
                'author' => $dua->is_anonymous || !$dua->user ? null : [
                    'id' => $dua->user->id,
                    'name' => $dua->user->name,
                    'email' => $dua->user->email,
                ],
                'status' => $dua->status,
                'deleted_at' => $dua->deleted_at?->toDateTimeString(),
                'hidden_by' => $dua->hiddenBy ? [
                    'id' => $dua->hiddenBy->id,
                    'name' => $dua->hiddenBy->name,
                ] : null,
                'hidden_at' => $dua->hidden_at?->toDateTimeString(),
                'prayers_count' => $dua->prayers_count ?? 0,
                'created_at' => $dua->created_at->diffForHumans(),
            ];
        });

        return Inertia::render('Admin/DuaWall/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $request->status,
                'deleted' => $request->deleted,
                'search' => $request->search,
            ],
        ]);
    }

    public function hide(Request $request, DuaRequest $dua)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $dua->update([
            'status' => 'hidden',
            'hidden_by' => Auth::id(),
            'hidden_at' => now(),
        ]);

        // Log moderation action
        ModerationAction::create([
            'moderator_id' => Auth::id(),
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'hide',
            'reason' => $request->input('reason'),
        ]);

        return back()->with('success', 'Dua request hidden successfully.');
    }

    public function unhide(Request $request, DuaRequest $dua)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $dua->update([
            'status' => 'active',
            'hidden_by' => null,
            'hidden_at' => null,
        ]);

        // Log moderation action
        ModerationAction::create([
            'moderator_id' => Auth::id(),
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'unhide',
            'reason' => $request->input('reason'),
        ]);

        return back()->with('success', 'Dua request unhidden successfully.');
    }

    public function destroy(DuaRequest $dua)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $dua->delete();

        // Log moderation action
        ModerationAction::create([
            'moderator_id' => Auth::id(),
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'delete',
        ]);

        return back()->with('success', 'Dua request deleted successfully.');
    }

    public function restore($id)
    {
        if (!Auth::user()->is_admin) {
            abort(403);
        }

        $dua = DuaRequest::withTrashed()->findOrFail($id);
        $dua->restore();

        // Log moderation action
        ModerationAction::create([
            'moderator_id' => Auth::id(),
            'target_type' => 'dua_request',
            'target_id' => $dua->id,
            'action' => 'restore',
        ]);

        return back()->with('success', 'Dua request restored successfully.');
    }
}
