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
     * Display habits for the user.
     * Shows only lesson-based habits where the user has completed the linked lesson video.
     */
    public function index()
    {
        $user = auth()->user();

        // Lesson IDs the user has completed (video watched / lesson marked complete)
        $completedLessonIds = \App\Models\LessonProgress::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');

        $habits = Habit::where('is_active', true)
            ->where(function($q) use ($user, $completedLessonIds) {
                $q->where('user_id', $user->id); // Legacy user-specific habits
                if ($completedLessonIds->isNotEmpty()) {
                    $q->orWhere(function($sq) use ($completedLessonIds) {
                        $sq->whereIn('lesson_id', $completedLessonIds)->whereNull('user_id');
                    });
                }
            })
            ->with(['lesson', 'logs' => function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereDate('log_date', '>=', now()->subDays(30));
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function($habit) use ($user) {
                $userLogs = $habit->logs->where('user_id', $user->id);
                $currentStreak = $this->calculateStreakForUser($habit, $user);
                $longestStreak = $this->calculateLongestStreakForUser($habit, $user);

                $todayLog = $habit->logs()
                    ->where('user_id', $user->id)
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
                    'lesson_title' => $habit->lesson?->title ?? null,
                ];
            });

        // Overall completion rate (last 30 days) and best streak across all habits
        $completionRate = $this->calculateOverallCompletionRate($user);
        $bestStreak = $habits->isEmpty() ? 0 : $habits->max('current_streak');

        return Inertia::render('Habits/Index', [
            'habits' => $habits,
            'completion_rate' => $completionRate,
            'current_streak' => $bestStreak,
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

        // Check if this user already logged today (handles shared lesson-based habits)
        $existingLog = $habit->logs()
            ->where('user_id', auth()->id())
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

        // WhatsApp trigger: 3-day habit streak
        $user = auth()->user();
        $currentStreak = $this->calculateStreakForUser($habit, $user);
        if ($currentStreak === 3) {
            $triggerService = app(\App\Services\WhatsApp\TriggerService::class);
            $triggerService->fireAsync('habit_3_day_streak', $user);
        }

        return back()->with('success', 'Habit logged successfully!');
    }

    /**
     * Calculate current streak for a user's logs of a habit.
     */
    private function calculateStreakForUser(Habit $habit, $user): int
    {
        $logs = $habit->logs()
            ->where('user_id', $user->id)
            ->whereDate('log_date', '>=', now()->subDays(30))
            ->orderBy('log_date', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($logs->groupBy(fn($log) => $log->log_date->format('Y-m-d')) as $date => $dayLogs) {
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
     * Calculate longest streak for a user's logs of a habit.
     */
    private function calculateLongestStreakForUser(Habit $habit, $user): int
    {
        $logs = $habit->logs()
            ->where('user_id', $user->id)
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

    /**
     * Calculate overall completion rate (last 30 days).
     * (Total days logged across habits) / (habits × 30 days).
     */
    private function calculateOverallCompletionRate($user): float
    {
        $completedLessonIds = \App\Models\LessonProgress::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->pluck('lesson_id');

        $habits = Habit::where('is_active', true)
            ->where(function($q) use ($user, $completedLessonIds) {
                $q->where('user_id', $user->id);
                if ($completedLessonIds->isNotEmpty()) {
                    $q->orWhere(fn($sq) => $sq->whereIn('lesson_id', $completedLessonIds)->whereNull('user_id'));
                }
            })
            ->get();

        if ($habits->isEmpty()) {
            return 0;
        }

        $startDate = now()->subDays(30)->startOfDay();
        $endDate = now()->endOfDay();
        $totalCompleted = 0;

        foreach ($habits as $habit) {
            $uniqueDates = $habit->logs()
                ->where('user_id', $user->id)
                ->whereBetween('log_date', [$startDate, $endDate])
                ->get()
                ->pluck('log_date')
                ->map(fn($d) => $d->format('Y-m-d'))
                ->unique();
            $totalCompleted += min(30, $uniqueDates->count());
        }

        $totalExpected = $habits->count() * 30;
        return round(min(100, ($totalCompleted / $totalExpected) * 100), 1);
    }
}
