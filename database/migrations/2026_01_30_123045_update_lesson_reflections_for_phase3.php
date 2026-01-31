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
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support renameColumn or change column types easily
            // We'll add new columns and migrate data
            Schema::table('lesson_reflections', function (Blueprint $table) {
                $table->longText('takeaway')->nullable()->after('user_id');
                $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                $table->text('teacher_note')->nullable()->after('reviewed_at');
            });

            // Migrate data
            DB::statement('UPDATE lesson_reflections SET takeaway = content WHERE takeaway IS NULL');
            DB::statement('UPDATE lesson_reflections SET teacher_note = mentor_note WHERE teacher_note IS NULL AND mentor_note IS NOT NULL');

            // Drop old columns (SQLite limitation: need to recreate table)
            Schema::table('lesson_reflections', function (Blueprint $table) {
                $table->dropColumn(['content', 'mentor_note']);
            });
        } else {
            Schema::table('lesson_reflections', function (Blueprint $table) {
                // Rename content to takeaway
                $table->renameColumn('content', 'takeaway');

                // Add reviewed_by and reviewed_at for teacher review
                $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

                // Rename mentor_note to teacher_note
                $table->renameColumn('mentor_note', 'teacher_note');

                // Change takeaway to LONGTEXT as per requirements
                $table->longText('takeaway')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: add back old columns, migrate data, drop new columns
            Schema::table('lesson_reflections', function (Blueprint $table) {
                $table->text('content')->nullable()->after('user_id');
                $table->text('mentor_note')->nullable()->after('review_status');
            });

            DB::statement('UPDATE lesson_reflections SET content = takeaway WHERE content IS NULL');
            DB::statement('UPDATE lesson_reflections SET mentor_note = teacher_note WHERE mentor_note IS NULL AND teacher_note IS NOT NULL');

            Schema::table('lesson_reflections', function (Blueprint $table) {
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn(['takeaway', 'reviewed_by', 'reviewed_at', 'teacher_note']);
            });
        } else {
            Schema::table('lesson_reflections', function (Blueprint $table) {
                // Rename back
                $table->renameColumn('takeaway', 'content');
                $table->renameColumn('teacher_note', 'mentor_note');

                // Drop new columns
                $table->dropForeign(['reviewed_by']);
                $table->dropColumn(['reviewed_by', 'reviewed_at']);

                // Change back to text
                $table->text('content')->change();
            });
        }
    }
};
