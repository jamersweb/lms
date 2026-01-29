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
        Schema::create('micro_habit_nudges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_path'); // Path to 30-second audio clip
            $table->integer('duration_seconds')->default(30);
            $table->string('sunnah_topic'); // e.g., "Morning Adhkar", "Before Eating"
            $table->time('send_at'); // Time of day to send (e.g., "08:00:00")
            $table->json('target_days')->nullable(); // Days of week [1,2,3,4,5] for weekdays
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('micro_habit_nudges');
    }
};
