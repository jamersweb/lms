<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Habit;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HabitController extends Controller
{
    /**
     * Display all habits across all users.
     */
    public function index(Request $request)
    {
        $query = Habit::with('user');

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($uq) use ($request) {
                      $uq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        // Filter by user
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $habits = $query->withCount('logs')
            ->latest()
            ->paginate(15)
            ->through(fn($habit) => [
                'id' => $habit->id,
                'title' => $habit->title,
                'frequency_type' => $habit->frequency_type,
                'description' => $habit->description,
                'logs_count' => $habit->logs_count,
                'user' => [
                    'id' => $habit->user->id,
                    'name' => $habit->user->name,
                ],
                'created_at' => $habit->created_at->format('M d, Y'),
            ]);

        $users = User::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Habits/Index', [
            'habits' => $habits,
            'users' => $users,
            'filters' => [
                'search' => $request->search,
                'user_id' => $request->user_id,
            ],
        ]);
    }

    /**
     * Show form for creating a habit for a user.
     */
    public function create(Request $request)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $selectedUserId = $request->user_id;

        return Inertia::render('Admin/Habits/Create', [
            'users' => $users,
            'selectedUserId' => $selectedUserId,
        ]);
    }

    /**
     * Store a new habit for a user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency_type' => 'required|in:daily,weekly,custom',
            'target_per_day' => 'nullable|integer|min:1|max:10',
        ]);

        // Map validated data to match database schema
        $data = [
            'user_id' => $validated['user_id'],
            'title' => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'frequency_type' => $validated['frequency_type'] ?? 'daily',
            'target_per_day' => isset($validated['target_per_day']) ? (int)$validated['target_per_day'] : 1,
            'is_active' => true,
        ];

        // Create habit manually to ensure title is included
        $habit = new Habit();
        $habit->user_id = $data['user_id'];
        $habit->title = $data['title'];
        $habit->description = $data['description'];
        $habit->frequency_type = $data['frequency_type'];
        $habit->target_per_day = $data['target_per_day'];
        $habit->is_active = $data['is_active'];
        $habit->save();

        return redirect()->route('admin.habits.index')
            ->with('success', 'Habit created successfully for user.');
    }

    /**
     * Show form for editing a habit.
     */
    public function edit(Habit $habit)
    {
        return Inertia::render('Admin/Habits/Edit', [
            'habit' => [
                'id' => $habit->id,
                'title' => $habit->title,
                'description' => $habit->description,
                'frequency_type' => $habit->frequency_type,
                'target_per_day' => $habit->target_per_day,
                'user' => [
                    'id' => $habit->user->id,
                    'name' => $habit->user->name,
                ],
            ],
        ]);
    }

    /**
     * Update the specified habit.
     */
    public function update(Request $request, Habit $habit)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency_type' => 'required|in:daily,weekly,custom',
            'target_per_day' => 'nullable|integer|min:1|max:10',
        ]);

        // Map validated data
        $data = [
            'title' => trim($validated['title']),
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'frequency_type' => $validated['frequency_type'],
            'target_per_day' => isset($validated['target_per_day']) ? (int)$validated['target_per_day'] : 1,
        ];

        $habit->update($data);

        return redirect()->route('admin.habits.index')
            ->with('success', 'Habit updated successfully.');
    }

    /**
     * Delete the specified habit.
     */
    public function destroy(Habit $habit)
    {
        $habit->delete();

        return redirect()->route('admin.habits.index')
            ->with('success', 'Habit deleted successfully.');
    }
}
