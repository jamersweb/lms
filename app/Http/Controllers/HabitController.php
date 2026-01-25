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
        auth()->user()->habits()->create($request->validated());

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
