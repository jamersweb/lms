<?php

namespace App\Providers;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\JournalEntry;
use App\Models\Note;
use App\Policies\DiscussionPolicy;
use App\Policies\DiscussionReplyPolicy;
use App\Policies\HabitLogPolicy;
use App\Policies\HabitPolicy;
use App\Policies\JournalEntryPolicy;
use App\Policies\NotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        
        // Register all policies
        Gate::policy(Habit::class, HabitPolicy::class);
        Gate::policy(HabitLog::class, HabitLogPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(Discussion::class, DiscussionPolicy::class);
        Gate::policy(DiscussionReply::class, DiscussionReplyPolicy::class);
    }
}
