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
        Schema::create('task_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_progress_id')->constrained('task_progress')->cascadeOnDelete();
            $table->date('checkin_on');
            $table->timestamps();

            // Unique constraint: prevents multiple check-ins per day
            $table->unique(['task_progress_id', 'checkin_on']);

            // Index for date queries
            $table->index('checkin_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_checkins');
    }
};
