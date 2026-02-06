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
        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
            $table->text('sunnah_pointers')->nullable(); // Short pointers for Sunnah
            $table->text('duas_text')->nullable(); // Arabic/Urdu/English Duas
            $table->string('audio_path')->nullable(); // Path for Dua audio files
            $table->string('pdf_path')->nullable(); // Path for lesson notes PDF
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
