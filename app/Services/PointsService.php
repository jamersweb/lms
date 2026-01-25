<?php

namespace App\Services;

use App\Models\User;
use App\Models\PointsEvent;

class PointsService
{
    /**
     * Award points to a user for an event.
     */
    public static function award(User $user, string $eventType, int $points): PointsEvent
    {
        return $user->pointsEvents()->create([
            'key' => uniqid($eventType . '_', true),
            'event_type' => $eventType,
            'points' => $points,
            'description' => self::getDescription($eventType)
        ]);
    }

    /**
     * Award points once for a unique event (idempotent).
     * If the event key already exists, return the existing event.
     */
    public function awardOnce(string $eventKey, int $userId, int $points, string $eventType): PointsEvent
    {
        // Check if event already exists
        $existing = PointsEvent::where('key', $eventKey)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Create new event
        return PointsEvent::create([
            'user_id' => $userId,
            'key' => $eventKey,
            'event_type' => $eventType,
            'points' => $points,
            'description' => self::getDescription($eventType)
        ]);
    }

    /**
     * Get total points for a user.
     */
    public function totalPoints(int $userId): int
    {
        return PointsEvent::where('user_id', $userId)->sum('points');
    }

    /**
     * Get description for an event type.
     */
    private static function getDescription(string $eventType): string
    {
        return match($eventType) {
            'lesson_completed' => 'Completed a lesson',
            'habit_completed' => 'Completed a daily habit',
            'habit_done' => 'Completed a daily habit',
            'journal_entry' => 'Wrote a journal entry',
            'discussion_created' => 'Started a discussion',
            'discussion_reply' => 'Replied to a discussion',
            '7_day_streak' => '7-day learning streak',
            'course_completed' => 'Completed a course',
            default => 'Points earned'
        };
    }

    /**
     * Get total points for a user (static version).
     */
    public static function getTotalPoints(User $user): int
    {
        return $user->pointsEvents()->sum('points');
    }
}
