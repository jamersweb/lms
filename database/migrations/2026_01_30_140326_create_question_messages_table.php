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
        Schema::create('question_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('sender_role'); // student|teacher|admin
            $table->longText('message');
            $table->boolean('is_internal')->default(false); // teacher-only note
            $table->string('audio_type')->nullable(); // upload|url
            $table->string('audio_path')->nullable(); // if upload
            $table->text('audio_url')->nullable(); // if url
            $table->timestamp('read_at')->nullable(); // read receipt
            $table->timestamps();

            // Indexes
            $table->index(['question_id', 'created_at']);
            $table->index(['sender_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_messages');
    }
};
