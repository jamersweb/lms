<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Guard Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for video watch tracking and completion validation.
    |
    */

    /**
     * Maximum allowed playback rate (speed multiplier).
     * Attempts to exceed this will be logged as violations and block completion.
     */
    'max_playback_rate' => env('VIDEO_GUARD_MAX_PLAYBACK_RATE', 1.5),

    /**
     * Minimum watch ratio required for completion.
     * User must watch at least this percentage of the lesson duration.
     * Example: 0.95 means 95% of duration must be watched.
     */
    'min_watch_ratio' => env('VIDEO_GUARD_MIN_WATCH_RATIO', 0.95),

    /**
     * Minimum watch seconds (safety threshold for very short videos).
     * Even if ratio is met, user must watch at least this many seconds.
     */
    'min_watch_seconds' => env('VIDEO_GUARD_MIN_WATCH_SECONDS', 30),

    /**
     * Maximum forward jump allowed (in seconds).
     * Forward seeks beyond this threshold will be detected and block completion.
     * Small jumps are allowed for buffering/network issues.
     */
    'max_forward_jump_seconds' => env('VIDEO_GUARD_MAX_FORWARD_JUMP', 5),

    /**
     * Heartbeat grace seconds for delta clamping.
     * Server will clamp watched delta to heartbeat_interval + this value.
     */
    'heartbeat_grace_seconds' => env('VIDEO_GUARD_HEARTBEAT_GRACE', 20),

    /**
     * Require duration for completion.
     * If true, lessons without duration_seconds cannot be completed.
     * If false, completion allowed but missing_duration violation logged.
     */
    'require_duration_for_completion' => env('VIDEO_GUARD_REQUIRE_DURATION', true),
];
