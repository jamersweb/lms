<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL/PostgreSQL: rename columns
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('lesson_watch_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('lesson_watch_sessions', 'watch_time_seconds') &&
                    !Schema::hasColumn('lesson_watch_sessions', 'watched_seconds')) {
                    $table->renameColumn('watch_time_seconds', 'watched_seconds');
                }
                if (Schema::hasColumn('lesson_watch_sessions', 'last_time_seconds') &&
                    !Schema::hasColumn('lesson_watch_sessions', 'last_position_seconds')) {
                    $table->renameColumn('last_time_seconds', 'last_position_seconds');
                }
                if (Schema::hasColumn('lesson_watch_sessions', 'seek_events_count') &&
                    !Schema::hasColumn('lesson_watch_sessions', 'seek_attempts')) {
                    $table->renameColumn('seek_events_count', 'seek_attempts');
                }
            });
        } else {
            // SQLite: add new columns, copy data, drop old columns (handled in separate migration if needed)
            // For now, just add missing columns
            Schema::table('lesson_watch_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('lesson_watch_sessions', 'watched_seconds')) {
                    $table->integer('watched_seconds')->default(0)->after('ended_at');
                }
                if (!Schema::hasColumn('lesson_watch_sessions', 'last_position_seconds')) {
                    $table->float('last_position_seconds')->default(0)->after('watched_seconds');
                }
                if (!Schema::hasColumn('lesson_watch_sessions', 'seek_attempts')) {
                    $table->unsignedInteger('seek_attempts')->default(0)->after('max_playback_rate');
                }
            });
        }

        // Add violations JSON column for all drivers
        Schema::table('lesson_watch_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('lesson_watch_sessions', 'violations')) {
                $table->json('violations')->nullable()->after('seek_attempts');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_watch_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_watch_sessions', 'violations')) {
                $table->dropColumn('violations');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('lesson_watch_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('lesson_watch_sessions', 'watched_seconds')) {
                    $table->renameColumn('watched_seconds', 'watch_time_seconds');
                }
                if (Schema::hasColumn('lesson_watch_sessions', 'last_position_seconds')) {
                    $table->renameColumn('last_position_seconds', 'last_time_seconds');
                }
                if (Schema::hasColumn('lesson_watch_sessions', 'seek_attempts')) {
                    $table->renameColumn('seek_attempts', 'seek_events_count');
                }
            });
        }
    }
};
