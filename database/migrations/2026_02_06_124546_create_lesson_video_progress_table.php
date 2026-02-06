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
        Schema::create('lesson_video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->string('provider')->default('html5'); // html5|youtube|vimeo|other
            $table->integer('duration_seconds')->default(0);
            $table->integer('last_position_seconds')->default(0);
            $table->decimal('percent_complete', 5, 2)->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Unique index: one progress record per user per lesson
            $table->unique(['user_id', 'lesson_id'], 'lesson_video_progress_user_lesson_unique');
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('lesson_id');
            $table->index('course_id');
            $table->index('is_completed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_video_progress');
    }
};
