<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make user_id nullable - habits can be lesson-based (no user)
        Schema::table('habits', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        // Fix habit_logs: one log per habit per user per date (for shared lesson-based habits)
        // Add new unique first (so habit_id FK has an index), then drop old unique
        Schema::table('habit_logs', function (Blueprint $table) {
            $table->unique(['habit_id', 'user_id', 'log_date'], 'habit_logs_habit_user_date_unique');
        });
        Schema::table('habit_logs', function (Blueprint $table) {
            $table->dropUnique(['habit_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::table('habit_logs', function (Blueprint $table) {
            $table->unique(['habit_id', 'log_date']);
        });
        Schema::table('habit_logs', function (Blueprint $table) {
            $table->dropUnique('habit_logs_habit_user_date_unique');
        });

        Schema::table('habits', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
