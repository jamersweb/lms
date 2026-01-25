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
    Route::get('/certificates', function() { 
        return Inertia::render('Certificates/Index'); 
    })->name('certificates.index');
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
    
    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('/users/{user}/toggle-admin', [\App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    
    // Habit Management (admin can create habits for users)
    Route::resource('habits', \App\Http\Controllers\Admin\HabitController::class);
});

// Auth routes (provided by Breeze)
require __DIR__.'/auth.php';

