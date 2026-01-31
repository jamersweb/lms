<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lesson Progression Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sequential lesson unlocking and progression rules.
    |
    */

    /**
     * Enable sequential lesson unlocking.
     * When true, lessons must be completed in order within each module.
     */
    'sequential_lessons' => env('PROGRESSION_SEQUENTIAL_LESSONS', true),

    /**
     * One-at-a-time access mode.
     * When true, only the first incomplete lesson in a module is accessible.
     * When false, all lessons up to the first incomplete are accessible.
     */
    'one_at_a_time' => env('PROGRESSION_ONE_AT_A_TIME', true),
];
