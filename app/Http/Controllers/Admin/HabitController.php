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
                $q->where('name', 'like', "%{$request->search}%")
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
                'name' => $habit->name,
                'frequency' => $habit->frequency,
                'current_streak' => $habit->current_streak,
                'best_streak' => $habit->best_streak,
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:daily,weekly',
            'reminder_time' => 'nullable|date_format:H:i',
        ]);
        
        Habit::create($validated);
        
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
                'name' => $habit->name,
                'description' => $habit->description,
                'frequency' => $habit->frequency,
                'reminder_time' => $habit->reminder_time,
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:daily,weekly',
            'reminder_time' => 'nullable|date_format:H:i',
        ]);
        
        $habit->update($validated);
        
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
