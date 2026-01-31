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
        Schema::create('course_progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('lessons_total')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('reflections_required')->default(0);
            $table->integer('reflections_done')->default(0);
            $table->integer('tasks_required')->default(0);
            $table->integer('tasks_done')->default(0);
            $table->foreignId('next_lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->timestamp('next_lesson_release_at')->nullable();
            $table->string('blocked_by')->nullable(); // reflection_required/task_incomplete/not_released_yet/etc.
            $table->timestamp('updated_at')->useCurrent();

            // Unique constraint
            $table->unique(['user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_progress_snapshots');
    }
};
