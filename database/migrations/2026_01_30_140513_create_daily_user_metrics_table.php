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
        Schema::create('daily_user_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->integer('active_seconds')->default(0);
            $table->integer('watched_seconds')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('reflections_submitted')->default(0);
            $table->integer('task_checkins')->default(0);
            $table->integer('violations_count')->default(0);
            $table->integer('seek_attempts')->default(0);
            $table->decimal('max_playback_rate', 3, 1)->default(1.0);
            $table->integer('stagnation_score')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_user_metrics');
    }
};
