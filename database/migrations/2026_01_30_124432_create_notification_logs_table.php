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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // 'drip', 'task', 'stagnation'
            $table->json('meta')->nullable(); // Store lesson_id, task_id, etc.
            $table->date('sent_on'); // Date when sent (for daily deduplication)
            $table->timestamps();

            // Prevent duplicate notifications per user per type per day
            // Note: Meta can contain lesson_id or task_id for more granular deduplication,
            // but the unique constraint is on (user_id, type, sent_on) to prevent spam
            $table->unique(['user_id', 'type', 'sent_on'], 'notification_logs_unique');
            $table->index(['user_id', 'type']);
            $table->index('sent_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
