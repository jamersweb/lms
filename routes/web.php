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

    // Inbox
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{broadcast}', [\App\Http\Controllers\InboxController::class, 'show'])->name('inbox.show');

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
    Route::get('/tasks/{task}', [\App\Http\Controllers\TaskProgressController::class, 'show'])
        ->name('tasks.show');
    Route::post('/tasks/{task}/checkin', [\App\Http\Controllers\TaskProgressController::class, 'checkin'])
        ->name('tasks.checkin');
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

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Voice Notes (student view)
    Route::get('/profile/voice-notes', [\App\Http\Controllers\UserVoiceNoteController::class, 'index'])->name('voice-notes.index');

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

    // Ask Portal (student) - Legacy
    Route::get('/ask', [\App\Http\Controllers\AskThreadController::class, 'index'])->name('ask.index');
    Route::get('/ask/create', [\App\Http\Controllers\AskThreadController::class, 'create'])->name('ask.create');
    Route::post('/ask', [\App\Http\Controllers\AskThreadController::class, 'store'])->name('ask.store');
    Route::get('/ask/{thread}', [\App\Http\Controllers\AskThreadController::class, 'show'])->name('ask.show');
    Route::post('/ask/{thread}/reply', [\App\Http\Controllers\AskThreadController::class, 'reply'])->name('ask.reply');

    // Questions Portal (student) - New implementation
    Route::get('/questions', [\App\Http\Controllers\StudentQuestionsController::class, 'index'])->name('questions.index');
    Route::get('/questions/create', [\App\Http\Controllers\StudentQuestionsController::class, 'create'])->name('questions.create');
    Route::post('/questions', [\App\Http\Controllers\StudentQuestionsController::class, 'store'])
        ->name('questions.store')
        ->middleware('throttle:5,1'); // 5 per hour
    Route::get('/questions/{question}', [\App\Http\Controllers\StudentQuestionsController::class, 'show'])->name('questions.show');
    Route::post('/questions/{question}/message', [\App\Http\Controllers\StudentQuestionsController::class, 'message'])
        ->name('questions.message')
        ->middleware('throttle:10,1'); // 10 per hour
    Route::patch('/questions/{question}/resolve', [\App\Http\Controllers\StudentQuestionsController::class, 'markResolved'])
        ->name('questions.resolve');

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

    // Content Rules management
    Route::put('/content-rules/{type}/{id}', [\App\Http\Controllers\Admin\ContentRuleController::class, 'upsert'])->name('content-rules.upsert');
    Route::delete('/content-rules/{type}/{id}', [\App\Http\Controllers\Admin\ContentRuleController::class, 'destroy'])->name('content-rules.destroy');
    Route::get('/lesson-reflections', [\App\Http\Controllers\Admin\LessonReflectionController::class, 'index'])
        ->name('lesson-reflections.index');
    Route::get('/lesson-reflections/{reflection}', [\App\Http\Controllers\Admin\LessonReflectionController::class, 'show'])
        ->name('lesson-reflections.show');
    Route::patch('/lesson-reflections/{reflection}', [\App\Http\Controllers\Admin\LessonReflectionController::class, 'update'])
        ->name('lesson-reflections.update');

    // Task Management
    Route::put('/lessons/{lesson}/task', [\App\Http\Controllers\Admin\TaskController::class, 'upsert'])
        ->name('lessons.task.upsert');
    Route::delete('/lessons/{lesson}/task', [\App\Http\Controllers\Admin\TaskController::class, 'destroy'])
        ->name('lessons.task.destroy');

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('/users/{user}/toggle-admin', [\App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::patch('/users/{user}/segmentation', [\App\Http\Controllers\Admin\UserSegmentationController::class, 'update'])->name('users.segmentation.update');

    // Habit Management (admin can create habits for users)
    Route::resource('habits', \App\Http\Controllers\Admin\HabitController::class);

    // Analytics
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/stagnation', [\App\Http\Controllers\Admin\StagnationController::class, 'index'])->name('analytics.stagnation');
    Route::get('/analytics/courses/{course}', [\App\Http\Controllers\Admin\CourseMasteryController::class, 'show'])->name('analytics.courses.show');
    Route::get('/analytics/users/{user}', [\App\Http\Controllers\Admin\UserAnalyticsController::class, 'show'])->name('analytics.users.show');
    Route::get('/users/{user}/activity', [\App\Http\Controllers\Admin\AnalyticsController::class, 'userActivity'])->name('users.activity');

    // Broadcasts
    Route::get('/broadcasts', [\App\Http\Controllers\Admin\BroadcastController::class, 'index'])
        ->name('broadcasts.index');
    Route::get('/broadcasts/create', [\App\Http\Controllers\Admin\BroadcastController::class, 'create'])
        ->name('broadcasts.create');
    Route::post('/broadcasts', [\App\Http\Controllers\Admin\BroadcastController::class, 'store'])
        ->name('broadcasts.store');
    Route::get('/broadcasts/{broadcast}', [\App\Http\Controllers\Admin\BroadcastController::class, 'show'])
        ->name('broadcasts.show');
    Route::patch('/broadcasts/{broadcast}', [\App\Http\Controllers\Admin\BroadcastController::class, 'update'])
        ->name('broadcasts.update');
    Route::post('/broadcasts/{broadcast}/preview', [\App\Http\Controllers\Admin\BroadcastController::class, 'preview'])
        ->name('broadcasts.preview');
    Route::post('/broadcasts/preview', [\App\Http\Controllers\Admin\BroadcastController::class, 'preview'])
        ->name('broadcasts.preview.new');
    Route::post('/broadcasts/{broadcast}/send', [\App\Http\Controllers\Admin\BroadcastController::class, 'send'])
        ->name('broadcasts.send');

    // Micro Habit Nudges
    Route::get('/micro-habit-nudges', [\App\Http\Controllers\Admin\MicroHabitNudgeController::class, 'index'])->name('micro-habit-nudges.index');
    Route::post('/micro-habit-nudges', [\App\Http\Controllers\Admin\MicroHabitNudgeController::class, 'store'])->name('micro-habit-nudges.store');
    Route::put('/micro-habit-nudges/{nudge}', [\App\Http\Controllers\Admin\MicroHabitNudgeController::class, 'update'])->name('micro-habit-nudges.update');

    // Ask Portal (admin/mentors) - Legacy
    Route::get('/ask', [\App\Http\Controllers\Admin\AskThreadController::class, 'index'])->name('ask.index');
    Route::get('/ask/{thread}', [\App\Http\Controllers\Admin\AskThreadController::class, 'show'])->name('ask.show');
    Route::post('/ask/{thread}/reply', [\App\Http\Controllers\Admin\AskThreadController::class, 'reply'])->name('ask.reply');
    Route::post('/ask/{thread}/close', [\App\Http\Controllers\Admin\AskThreadController::class, 'close'])->name('ask.close');

    // Questions Portal (admin/mentors) - New implementation
    Route::get('/questions', [\App\Http\Controllers\Admin\AdminQuestionsController::class, 'index'])->name('questions.index');
    Route::get('/questions/{question}', [\App\Http\Controllers\Admin\AdminQuestionsController::class, 'show'])->name('questions.show');
    Route::post('/questions/{question}/message', [\App\Http\Controllers\Admin\AdminQuestionsController::class, 'message'])->name('questions.message');
    Route::patch('/questions/{question}', [\App\Http\Controllers\Admin\AdminQuestionsController::class, 'update'])->name('questions.update');

    // Voice Notes
    Route::post('/users/{user}/voice-notes', [\App\Http\Controllers\Admin\UserVoiceNoteController::class, 'store'])->name('users.voice-notes.store');

    // Notification Settings
    Route::get('/notifications/settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'index'])
        ->name('notifications.settings');
    Route::patch('/notifications/settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'update'])
        ->name('notifications.settings.update');

    // Micro Nudges (Sunnah of the Hour)
    Route::get('/micro-nudges/clips', [\App\Http\Controllers\Admin\AudioClipController::class, 'index'])
        ->name('micro-nudges.clips.index');
    Route::post('/micro-nudges/clips', [\App\Http\Controllers\Admin\AudioClipController::class, 'store'])
        ->name('micro-nudges.clips.store');
    Route::patch('/micro-nudges/clips/{clip}', [\App\Http\Controllers\Admin\AudioClipController::class, 'update'])
        ->name('micro-nudges.clips.update');
    Route::delete('/micro-nudges/clips/{clip}', [\App\Http\Controllers\Admin\AudioClipController::class, 'destroy'])
        ->name('micro-nudges.clips.destroy');

    Route::get('/micro-nudges/campaigns', [\App\Http\Controllers\Admin\MicroNudgeCampaignController::class, 'index'])
        ->name('micro-nudges.campaigns.index');
    Route::get('/micro-nudges/campaigns/create', [\App\Http\Controllers\Admin\MicroNudgeCampaignController::class, 'create'])
        ->name('micro-nudges.campaigns.create');
    Route::post('/micro-nudges/campaigns', [\App\Http\Controllers\Admin\MicroNudgeCampaignController::class, 'store'])
        ->name('micro-nudges.campaigns.store');
    Route::get('/micro-nudges/campaigns/{campaign}/edit', [\App\Http\Controllers\Admin\MicroNudgeCampaignController::class, 'edit'])
        ->name('micro-nudges.campaigns.edit');
    Route::patch('/micro-nudges/campaigns/{campaign}', [\App\Http\Controllers\Admin\MicroNudgeCampaignController::class, 'update'])
        ->name('micro-nudges.campaigns.update');
    Route::delete('/micro-nudges/campaigns/{campaign}', [\App\Http\Controllers\Admin\MicroNudgeCampaignController::class, 'destroy'])
        ->name('micro-nudges.campaigns.destroy');

    // Dua Wall Moderation
    Route::get('/dua-wall', [\App\Http\Controllers\Admin\DuaWallController::class, 'index'])
        ->name('dua-wall.index');
    Route::patch('/dua-wall/{dua}/hide', [\App\Http\Controllers\Admin\DuaWallController::class, 'hide'])
        ->name('dua-wall.hide');
    Route::patch('/dua-wall/{dua}/unhide', [\App\Http\Controllers\Admin\DuaWallController::class, 'unhide'])
        ->name('dua-wall.unhide');
    Route::delete('/dua-wall/{dua}', [\App\Http\Controllers\Admin\DuaWallController::class, 'destroy'])
        ->name('dua-wall.destroy');
    Route::patch('/dua-wall/{id}/restore', [\App\Http\Controllers\Admin\DuaWallController::class, 'restore'])
        ->name('dua-wall.restore');
});

// Auth routes (provided by Breeze)
require __DIR__.'/auth.php';

