<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHabitRequest;
use App\Http\Requests\UpdateHabitRequest;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Services\PointsService;
use Illuminate\Support\Facades\Log;

class HabitController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of user's habits.
     */
    public function index()
    {
        $user = auth()->user();

        $habits = $user->habits()
            ->where('is_active', true)
            ->with(['logs' => function($query) {
                $query->whereDate('log_date', '>=', now()->subDays(30));
            }])
            ->get()
            ->map(function($habit) {
                $currentStreak = $this->calculateStreak($habit);
                $longestStreak = $this->calculateLongestStreak($habit);

                // Check if logged today
                $todayLog = $habit->logs()
                    ->whereDate('log_date', today())
                    ->first();

                return [
                    'id' => $habit->id,
                    'title' => $habit->title,
                    'description' => $habit->description,
                    'target_per_day' => $habit->target_per_day ?? 1,
                    'current_streak' => $currentStreak,
                    'longest_streak' => $longestStreak,
                    'today_log' => $todayLog ? ['status' => 'done'] : null,
                    'is_active' => $habit->is_active,
                ];
            });

        return Inertia::render('Habits/Index', [
            'habits' => $habits
        ]);
    }

    /**
     * Store a newly created habit.
     */
    public function store(StoreHabitRequest $request)
    {
        // Get all request data first
        $allData = $request->all();

        // Validate the request - this will throw ValidationException if title is missing
        try {
            $validated = $request->validated();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput($request->except('password'));
        }

        // Double-check title exists and is not empty
        $title = trim($validated['title'] ?? $allData['title'] ?? '');

        if (empty($title)) {
            return redirect()->back()
                ->withErrors(['title' => 'The title field is required and cannot be empty.'])
                ->withInput($request->except('password'));
        }

        // Build data array ensuring all required fields are present
        $data = [
            'title' => $title,
            'description' => !empty($validated['description']) ? trim($validated['description']) : null,
            'frequency_type' => $validated['frequency_type'] ?? 'daily',
            'target_per_day' => isset($validated['target_per_day']) ? (int)$validated['target_per_day'] : 1,
            'is_active' => true,
        ];

        // Log for debugging
        Log::info('Creating habit', [
            'data' => $data,
            'validated' => $validated,
            'all_data' => $allData,
            'has_title' => isset($data['title']) && !empty($data['title']),
            'title_value' => $data['title'] ?? 'MISSING',
        ]);

        // Create the habit by manually setting each field to ensure title is included
        $habit = new Habit();
        $habit->user_id = auth()->id();
        $habit->title = $data['title']; // Explicitly set title first
        $habit->description = $data['description'];
        $habit->frequency_type = $data['frequency_type'];
        $habit->target_per_day = $data['target_per_day'];
        $habit->is_active = $data['is_active'];

        // Verify title is set before saving
        if (empty($habit->title)) {
            Log::error('Title is empty before save!', [
                'data' => $data,
                'habit_attributes' => $habit->getAttributes(),
            ]);
            return redirect()->back()
                ->withErrors(['title' => 'The title field is required and cannot be empty.'])
                ->withInput($request->except('password'));
        }

        $habit->save();

        // Verify it was created with title
        $habit->refresh();
        if (empty($habit->title)) {
            Log::error('Habit created without title!', [
                'habit_id' => $habit->id,
                'data_sent' => $data,
                'saved_attributes' => $habit->getAttributes(),
            ]);
            $habit->delete(); // Clean up
            return redirect()->back()
                ->withErrors(['title' => 'Failed to save habit title. Please try again.'])
                ->withInput($request->except('password'));
        }

        return redirect()->route('habits.index')
            ->with('success', 'Habit created successfully!');
    }

    /**
     * Display the specified habit.
     */
    public function show(Habit $habit)
    {
        $this->authorize('view', $habit);

        $habit->load(['logs' => function($query) {
            $query->latest()->limit(30);
        }]);

        return Inertia::render('Habits/Show', [
            'habit' => $habit,
            'logs' => $habit->logs,
            'streaks' => [
                'current' => $this->calculateStreak($habit),
                'longest' => $this->calculateLongestStreak($habit),
            ]
        ]);
    }

    /**
     * Update the specified habit.
     */
    public function update(UpdateHabitRequest $request, Habit $habit)
    {
        $habit->update($request->validated());

        return redirect()->route('habits.index')
            ->with('success', 'Habit updated successfully!');
    }

    /**
     * Remove the specified habit.
     */
    public function destroy(Habit $habit)
    {
        $this->authorize('delete', $habit);

        $habit->delete();

        return redirect()->route('habits.index')
            ->with('success', 'Habit deleted successfully!');
    }

    /**
     * Log a habit completion.
     */
    public function log(Request $request, Habit $habit)
    {
        $this->authorize('view', $habit);

        // Check if already logged today
        $existingLog = $habit->logs()
            ->whereDate('log_date', today())
            ->first();

        if ($existingLog) {
            return back()->with('info', 'Habit already logged for today.');
        }

        // Create log
        $habit->logs()->create([
            'user_id' => auth()->id(),
            'log_date' => today(),
            'status' => 'done',
            'completed_count' => 1
        ]);

        // Award points
        PointsService::award(auth()->user(), 'habit_done', 2);

        return back()->with('success', 'Habit logged successfully!');
    }

    /**
     * Calculate current streak for a habit.
     */
    private function calculateStreak(Habit $habit)
    {
        $logs = $habit->logs()
            ->whereDate('log_date', '>=', now()->subDays(30))
            ->orderBy('log_date', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($logs->groupBy(fn($log) => $log->log_date) as $date => $dayLogs) {
            if ($currentDate->format('Y-m-d') === $date || $currentDate->copy()->subDay()->format('Y-m-d') === $date) {
                $streak++;
                $currentDate = Carbon::parse($date)->startOfDay();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Calculate longest streak for a habit.
     */
    private function calculateLongestStreak(Habit $habit)
    {
        $logs = $habit->logs()
            ->orderBy('log_date', 'asc')
            ->get();

        if ($logs->isEmpty()) {
            return 0;
        }

        $longestStreak = 0;
        $currentStreak = 1;
        $previousDate = Carbon::parse($logs->first()->log_date)->startOfDay();

        foreach ($logs->skip(1) as $log) {
            $logDate = Carbon::parse($log->log_date)->startOfDay();

            if ($logDate->diffInDays($previousDate) === 1) {
                $currentStreak++;
            } else {
                $longestStreak = max($longestStreak, $currentStreak);
                $currentStreak = 1;
            }

            $previousDate = $logDate;
        }

        return max($longestStreak, $currentStreak);
    }
}
