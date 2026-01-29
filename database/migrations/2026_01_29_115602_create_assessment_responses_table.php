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
        Schema::create('assessment_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sunnah_assessment_id')->constrained()->cascadeOnDelete();
            $table->string('question_key'); // Key to identify which question in the JSON
            $table->boolean('already_practicing'); // true = already doing it, false = needs to learn
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'sunnah_assessment_id', 'question_key'], 'assess_resp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_responses');
    }
};
