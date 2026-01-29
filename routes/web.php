<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Welcome page - redirect to login or dashboard
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Courses
    Route::get('/courses', [\App\Http\Controllers\CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [\App\Http\Controllers\CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/enroll', [\App\Http\Controllers\CourseController::class, 'enroll'])->name('courses.enroll');

    // Lessons
    Route::get('/courses/{course}/lessons/{lesson}', [\App\Http\Controllers\LessonController::class, 'show'])->name('lessons.show');
    Route::post('/lessons/{lesson}/complete', [\App\Http\Controllers\LessonProgressController::class, 'complete'])
        ->name('lessons.complete')
        ->middleware('throttle:30,1');
    Route::post('/lessons/{lesson}/duration', [\App\Http\Controllers\LessonController::class, 'storeDuration'])
        ->name('lessons.duration');
    Route::post('/lessons/{lesson}/reflection', [\App\Http\Controllers\LessonReflectionController::class, 'store'])
        ->name('lessons.reflection');
    Route::prefix('/lessons/{lesson}/watch')->name('lessons.watch.')->group(function () {
        Route::post('/start', [\App\Http\Controllers\WatchSessionController::class, 'start'])->name('start');
        Route::post('/heartbeat', [\App\Http\Controllers\WatchSessionController::class, 'heartbeat'])->name('heartbeat');
        Route::post('/end', [\App\Http\Controllers\WatchSessionController::class, 'end'])->name('end');
    });

    // Search
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

    // Habits
    Route::get('/habits', [\App\Http\Controllers\HabitController::class, 'index'])->name('habits.index');
    Route::post('/habits', [\App\Http\Controllers\HabitController::class, 'store'])->name('habits.store');
    Route::get('/habits/{habit}', [\App\Http\Controllers\HabitController::class, 'show'])->name('habits.show');
    Route::put('/habits/{habit}', [\App\Http\Controllers\HabitController::class, 'update'])->name('habits.update');
    Route::post('/habits/{habit}/log', [\App\Http\Controllers\HabitController::class, 'log'])->name('habits.log');
    Route::delete('/habits/{habit}', [\App\Http\Controllers\HabitController::class, 'destroy'])->name('habits.destroy');

    // Journal
    Route::get('/journal', [\App\Http\Controllers\JournalController::class, 'index'])->name('journal.index');
    Route::post('/journal', [\App\Http\Controllers\JournalController::class, 'store'])->name('journal.store');

    // Notes
    Route::resource('notes', \App\Http\Controllers\NoteController::class)->except(['create', 'edit', 'show']);

    // Discussions
    Route::get('/courses/{course}/discussions', [\App\Http\Controllers\DiscussionController::class, 'index'])->name('courses.discussions.index');
    Route::post('/courses/{course}/discussions', [\App\Http\Controllers\DiscussionController::class, 'store'])
        ->name('courses.discussions.store')
        ->middleware('throttle:10,1');
    Route::get('/discussions/{discussion}', [\App\Http\Controllers\DiscussionController::class, 'show'])->name('discussions.show');
    Route::post('/discussions/{discussion}/replies', [\App\Http\Controllers\DiscussionReplyController::class, 'store'])
        ->name('discussions.replies.store')
        ->middleware('throttle:20,1');

    // Leaderboard
    Route::get('/leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])->name('leaderboard.index');

    // Certificates
    Route::get('/certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('certificates.download');

    // Sunnah Assessment
    Route::get('/courses/{course}/assessment', [\App\Http\Controllers\SunnahAssessmentController::class, 'show'])->name('assessments.show');
    Route::post('/courses/{course}/assessment', [\App\Http\Controllers\SunnahAssessmentController::class, 'store'])->name('assessments.store');

    // Dev utilities
    Route::get('/dev/youtube-test', function () {
        return Inertia::render('Dev/YouTubeTest');
    })->name('dev.youtube-test');

    // Ask Portal (student)
    Route::get('/ask', [\App\Http\Controllers\AskThreadController::class, 'index'])->name('ask.index');
    Route::get('/ask/create', [\App\Http\Controllers\AskThreadController::class, 'create'])->name('ask.create');
    Route::post('/ask', [\App\Http\Controllers\AskThreadController::class, 'store'])->name('ask.store');
    Route::get('/ask/{thread}', [\App\Http\Controllers\AskThreadController::class, 'show'])->name('ask.show');
    Route::post('/ask/{thread}/reply', [\App\Http\Controllers\AskThreadController::class, 'reply'])->name('ask.reply');

    // Voice Notes
    Route::post('/ask/{thread}/voice-note', [\App\Http\Controllers\VoiceNoteController::class, 'store'])->name('ask.voice-note');
    Route::delete('/voice-notes/{voiceNote}', [\App\Http\Controllers\VoiceNoteController::class, 'destroy'])->name('voice-notes.destroy');

    // Dua Wall
    Route::get('/dua-wall', [\App\Http\Controllers\DuaWallController::class, 'index'])->name('dua.index');
    Route::post('/dua-wall', [\App\Http\Controllers\DuaWallController::class, 'store'])
        ->name('dua.store')
        ->middleware('throttle:10,1');
    Route::post('/dua-wall/{dua}/pray', [\App\Http\Controllers\DuaWallController::class, 'pray'])
        ->name('dua.pray')
        ->middleware('throttle:30,1');
});

// Admin routes
Route::middleware(['auth', \App\Http\Middleware\IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/health', \App\Http\Controllers\AdminHealthController::class)->name('health');
    Route::get('/moderation', [\App\Http\Controllers\Admin\ModerationController::class, 'index'])->name('moderation.index');
    Route::post('/moderation/handle', [\App\Http\Controllers\Admin\ModerationController::class, 'handle'])->name('moderation.handle');

    // Admin CRUD for courses, modules, lessons
    Route::resource('courses', \App\Http\Controllers\Admin\CourseController::class);
    Route::resource('modules', \App\Http\Controllers\Admin\ModuleController::class);
    Route::resource('lessons', \App\Http\Controllers\Admin\LessonController::class);
    Route::get('/lesson-reflections', [\App\Http\Controllers\Admin\LessonReflectionController::class, 'index'])
        ->name('lesson-reflections.index');
    Route::patch('/lesson-reflections/{reflection}', [\App\Http\Controllers\Admin\LessonReflectionController::class, 'update'])
        ->name('lesson-reflections.update');

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('/users/{user}/toggle-admin', [\App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');

    // Habit Management (admin can create habits for users)
    Route::resource('habits', \App\Http\Controllers\Admin\HabitController::class);

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/users/{user}/activity', [\App\Http\Controllers\Admin\AnalyticsController::class, 'userActivity'])->name('users.activity');

    // Broadcasts
    Route::get('/broadcasts', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])->name('broadcasts.index');
    Route::post('/broadcasts', [\App\Http\Controllers\Admin\BroadcastController::class, 'store'])->name('broadcasts.store');

    // Micro Habit Nudges
    Route::get('/micro-habit-nudges', [\App\Http\Controllers\Admin\MicroHabitNudgeController::class, 'index'])->name('micro-habit-nudges.index');
    Route::post('/micro-habit-nudges', [\App\Http\Controllers\Admin\MicroHabitNudgeController::class, 'store'])->name('micro-habit-nudges.store');
    Route::put('/micro-habit-nudges/{nudge}', [\App\Http\Controllers\Admin\MicroHabitNudgeController::class, 'update'])->name('micro-habit-nudges.update');

    // Ask Portal (admin/mentors)
    Route::get('/ask', [\App\Http\Controllers\Admin\AskThreadController::class, 'index'])->name('ask.index');
    Route::get('/ask/{thread}', [\App\Http\Controllers\Admin\AskThreadController::class, 'show'])->name('ask.show');
    Route::post('/ask/{thread}/reply', [\App\Http\Controllers\Admin\AskThreadController::class, 'reply'])->name('ask.reply');
    Route::post('/ask/{thread}/close', [\App\Http\Controllers\Admin\AskThreadController::class, 'close'])->name('ask.close');
});

// Auth routes (provided by Breeze)
require __DIR__.'/auth.php';

