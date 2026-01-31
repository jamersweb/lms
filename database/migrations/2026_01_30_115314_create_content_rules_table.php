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
        Schema::create('content_rules', function (Blueprint $table) {
            $table->id();
            $table->string('ruleable_type');
            $table->unsignedBigInteger('ruleable_id');
            $table->string('min_level', 20)->nullable(); // beginner, intermediate, expert
            $table->boolean('requires_bayah')->default(false);
            $table->string('gender', 10)->nullable(); // male, female
            $table->timestamps();

            // Index for polymorphic relationship
            $table->index(['ruleable_type', 'ruleable_id']);

            // Ensure only one rule per entity
            $table->unique(['ruleable_type', 'ruleable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_rules');
    }
};
