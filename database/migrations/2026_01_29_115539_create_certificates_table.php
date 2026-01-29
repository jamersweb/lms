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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // 'course_completion', 'level_up', 'milestone'
            $table->string('level')->nullable(); // 'beginner', 'intermediate', 'expert'
            $table->string('certificate_number')->unique();
            $table->date('issued_at');
            $table->text('metadata')->nullable(); // JSON data for certificate details
            $table->string('pdf_path')->nullable(); // Path to generated PDF
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
