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
        Schema::create('user_voice_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // recipient student
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); // teacher/admin
            $table->string('title')->nullable();
            $table->text('note')->nullable();
            $table->string('audio_type'); // upload|url
            $table->string('audio_path')->nullable(); // if upload
            $table->text('audio_url')->nullable(); // if url
            $table->boolean('is_private')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_voice_notes');
    }
};
