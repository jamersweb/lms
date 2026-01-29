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
        Schema::create('voice_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ask_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('audio_path'); // Path to stored audio file
            $table->integer('duration_seconds')->nullable();
            $table->text('transcription')->nullable(); // Optional transcription
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_notes');
    }
};
