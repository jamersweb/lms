<?php

namespace App\Services;

use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;

class HabitStreakService
{
    public function getStreaks(Habit $habit): array
    {
        $logs = $habit->logs()
            ->where('status', 'done')
            ->orderBy('log_date', 'desc')
            ->get();

        if ($logs->isEmpty()) {
            return ['current' => 0, 'longest' => 0];
        }

        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;

        // Calculate current streak
        // Check if today or yesterday was logged as done
        $lastLog = $logs->first();
        $lastLogDate = Carbon::parse($lastLog->log_date);
        $today = Carbon::today();
        
        // If the last log is older than yesterday, current streak is 0
        if ($lastLogDate->lt($today->copy()->subDay())) {
             $currentStreak = 0;
        } else {
             // Iterate backwards from the latest log
             // Note: Logs are already ordered desc by date
             // We need to verify consecutive days
             $expectedDate = $lastLogDate->copy();
             
             foreach ($logs as $log) {
                 $logDate = Carbon::parse($log->log_date);
                 
                 // If this log roughly matches expected date (same day)
                 if ($logDate->isSameDay($expectedDate)) {
                     $currentStreak++;
                     $expectedDate->subDay();
                 } else {
                     // Gap found
                     break;
                 }
             }
        }

        // Calculate longest streak
        // We can iterate all logs and just find the max sequence of consecutive days
        // Since logs are ordered desc, we can go through them
        $tempStreak = 1;
        $expectedDate = Carbon::parse($logs->first()->log_date)->subDay();
        
        // Reset longest to at least 1 if we have logs
        $longestStreak = 1;

        for ($i = 0; $i < $logs->count() - 1; $i++) {
            $currentDate = Carbon::parse($logs[$i]->log_date);
            $nextDate = Carbon::parse($logs[$i+1]->log_date); // This is older

            if ($nextDate->isSameDay($currentDate->copy()->subDay())) {
                $tempStreak++;
            } else {
                $longestStreak = max($longestStreak, $tempStreak);
                $tempStreak = 1;
            }
        }
        $longestStreak = max($longestStreak, $tempStreak);

        return [
            'current' => $currentStreak,
            'longest' => $longestStreak,
        ];
    }
}
