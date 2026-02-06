<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            // Add watched_seconds if missing (may already exist as time_watched_seconds)
            if (!Schema::hasColumn('lesson_progress', 'watched_seconds')) {
                if (Schema::hasColumn('lesson_progress', 'time_watched_seconds')) {
                    // Rename existing column
                    $table->renameColumn('time_watched_seconds', 'watched_seconds');
                } else {
                    $table->integer('watched_seconds')->default(0)->after('last_position_seconds');
                }
            }

            // Rename max_playback_rate_seen to max_playback_rate if needed (do this FIRST)
            if (Schema::hasColumn('lesson_progress', 'max_playback_rate_seen') &&
                !Schema::hasColumn('lesson_progress', 'max_playback_rate')) {
                $table->renameColumn('max_playback_rate_seen', 'max_playback_rate');
            }
        });

        // Separate callback to ensure rename is committed before using the column
        Schema::table('lesson_progress', function (Blueprint $table) {
            // Add seek_attempts if missing (after ensuring max_playback_rate exists)
            if (!Schema::hasColumn('lesson_progress', 'seek_attempts')) {
                // Determine which column to use as reference - use verified_at as safe fallback
                $afterColumn = 'verified_at'; // Safe fallback that should exist
                if (Schema::hasColumn('lesson_progress', 'max_playback_rate')) {
                    $afterColumn = 'max_playback_rate';
                } elseif (Schema::hasColumn('lesson_progress', 'seek_detected')) {
                    $afterColumn = 'seek_detected';
                }
                $table->integer('seek_attempts')->default(0)->after($afterColumn);
            }

            // Add violations JSON if missing
            if (!Schema::hasColumn('lesson_progress', 'violations')) {
                $table->json('violations')->nullable()->after('seek_attempts');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_progress', 'violations')) {
                $table->dropColumn('violations');
            }

            if (Schema::hasColumn('lesson_progress', 'seek_attempts')) {
                $table->dropColumn('seek_attempts');
            }

            // Rename back if we renamed
            if (Schema::hasColumn('lesson_progress', 'watched_seconds') &&
                !Schema::hasColumn('lesson_progress', 'time_watched_seconds')) {
                $table->renameColumn('watched_seconds', 'time_watched_seconds');
            }

            if (Schema::hasColumn('lesson_progress', 'max_playback_rate') &&
                !Schema::hasColumn('lesson_progress', 'max_playback_rate_seen')) {
                $table->renameColumn('max_playback_rate', 'max_playback_rate_seen');
            }
        });
    }
};
