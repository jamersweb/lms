<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonWatchSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WatchTrackingService
{
    /**
     * Heartbeat interval in seconds (used for delta validation).
     */
    private const HEARTBEAT_INTERVAL = 15;

    /**
     * Start a new watch session for a lesson.
     *
     * Returns existing active session if one exists within last 5 minutes (idempotent).
     */
    public function startSession(User $user, Lesson $lesson): LessonWatchSession
    {
        // Check for existing active session (not ended, started within last 5 minutes)
        $existingSession = LessonWatchSession::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->whereNull('ended_at')
            ->where('started_at', '>=', now()->subMinutes(5))
            ->first();

        if ($existingSession) {
            return $existingSession;
        }

        return LessonWatchSession::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'started_at' => now(),
            'watched_seconds' => 0,
            'max_playback_rate' => 1.0,
            'seek_attempts' => 0,
            'violations' => [],
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 1000),
        ]);
    }

    /**
     * Record a heartbeat during watch session.
     *
     * Validates and processes:
     * - Playback rate (clamps to 0.5 minimum, tracks max)
     * - Position delta (validates and clamps)
     * - Seek detection
     * - Visibility tracking
     *
     * Returns array with updated stats.
     */
    public function recordHeartbeat(User $user, Lesson $lesson, array $payload): array
    {
        $sessionId = $payload['session_id'];
        $positionSeconds = (float) $payload['position_seconds'];
        $playbackRate = (float) $payload['playback_rate'];
        $playedDeltaSeconds = isset($payload['played_delta_seconds']) ? (int) $payload['played_delta_seconds'] : null;
        $visibility = $payload['visibility'] ?? 'visible';
        $isSeeking = $payload['is_seeking'] ?? false;

        // Validate session belongs to user and lesson
        $session = LessonWatchSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        if ($session->ended_at) {
            return ['ignored' => true];
        }

        // Clamp playback rate to minimum 0.5
        $playbackRate = max(0.5, $playbackRate);

        // Track max playback rate
        $session->max_playback_rate = max((float) $session->max_playback_rate, $playbackRate);

        // Compute delta safely
        $lastPosition = (float) ($session->last_position_seconds ?? 0);
        $delta = $this->computeDelta($lastPosition, $positionSeconds, $playedDeltaSeconds);

        // Detect seek attempts using config threshold
        $maxForwardJump = config('video_guard.max_forward_jump_seconds', 5);
        $positionJump = $positionSeconds - $lastPosition;
        $isSeekDetected = $isSeeking || ($positionJump > $maxForwardJump);

        if ($isSeekDetected) {
            $session->seek_attempts = ($session->seek_attempts ?? 0) + 1;

            // Add violation
            $violations = $session->violations ?? [];
            $violations[] = [
                'type' => 'seek_forward',
                'at' => now()->toIso8601String(),
                'meta' => ['jump' => $positionJump, 'from' => $lastPosition, 'to' => $positionSeconds],
            ];
            $session->violations = $violations;
        }

        // Track rate violations using config threshold
        $maxPlaybackRate = config('video_guard.max_playback_rate', 1.5);
        if ($playbackRate > $maxPlaybackRate) {
            $violations = $session->violations ?? [];
            $violations[] = [
                'type' => 'rate_exceeded',
                'at' => now()->toIso8601String(),
                'meta' => ['rate' => $playbackRate, 'max_allowed' => $maxPlaybackRate],
            ];
            $session->violations = $violations;

            // Also record in progress violations
            $progressViolations = $progress->violations ?? [];
            $progressViolations[] = [
                'type' => 'rate_exceeded',
                'at' => now()->toIso8601String(),
                'meta' => ['rate' => $playbackRate, 'max_allowed' => $maxPlaybackRate],
            ];
            $progress->violations = $progressViolations;
        }

        // Track visibility violations
        if ($visibility === 'hidden') {
            $violations = $session->violations ?? [];
            $violations[] = [
                'type' => 'tab_hidden',
                'at' => now()->toIso8601String(),
            ];
            $session->violations = $violations;
        }

        // Update session
        $session->watched_seconds = (int) ($session->watched_seconds ?? 0) + (int) round($delta);
        $session->last_position_seconds = $positionSeconds;
        $session->save();

        // Get or create lesson progress aggregate
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'is_completed' => false,
                'last_position_seconds' => 0,
                'watched_seconds' => 0,
                'max_playback_rate' => 1.0,
                'seek_attempts' => 0,
                'violations' => [],
            ]
        );

        $progress->watched_seconds = (int) ($progress->watched_seconds ?? 0) + (int) round($delta);
        $progress->last_position_seconds = max($progress->last_position_seconds ?? 0, (int) floor($positionSeconds));
        $progress->last_heartbeat_at = now();
        $progress->max_playback_rate = max((float) ($progress->max_playback_rate ?? 1.0), $playbackRate);

        if ($isSeekDetected) {
            $progress->seek_attempts = ($progress->seek_attempts ?? 0) + 1;

            // Merge violations (avoid duplicates)
            $progressViolations = $progress->violations ?? [];
            $progressViolations[] = [
                'type' => 'seek_forward',
                'at' => now()->toIso8601String(),
                'meta' => ['jump' => $positionJump, 'from' => $lastPosition, 'to' => $positionSeconds],
            ];
            $progress->violations = $progressViolations;
        }

        $progress->save();

        return [
            'ok' => true,
            'watched_seconds' => $session->watched_seconds,
            'seek_attempts' => $session->seek_attempts,
        ];
    }

    /**
     * End a watch session.
     */
    public function endSession(User $user, Lesson $lesson, int $sessionId): void
    {
        $session = LessonWatchSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->firstOrFail();

        if (!$session->ended_at) {
            $session->ended_at = now();
            $session->save();
        }
    }

    /**
     * Compute safe delta for watched time.
     *
     * If played_delta_seconds is provided, use it but clamp to reasonable range.
     * Otherwise, compute from position change and clamp.
     */
    private function computeDelta(float $lastPosition, float $currentPosition, ?int $playedDeltaSeconds): float
    {
        // If client provided delta, use it but clamp to heartbeat_interval + 5 seconds
        if ($playedDeltaSeconds !== null) {
            return max(0, min($playedDeltaSeconds, self::HEARTBEAT_INTERVAL + 5));
        }

        // Otherwise compute from position change
        $positionDelta = max(0, $currentPosition - $lastPosition);

        // Clamp to heartbeat_interval * 2 (allows for some buffering)
        return max(0, min($positionDelta, self::HEARTBEAT_INTERVAL * 2));
    }
}
