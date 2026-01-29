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
        Schema::table('lessons', function (Blueprint $table) {
            $table->integer('video_duration_seconds')->nullable()->after('duration_seconds');
            $table->boolean('requires_reflection')->default(false)->after('video_duration_seconds');
            $table->boolean('reflection_requires_approval')->default(false)->after('requires_reflection');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_duration_seconds', 'requires_reflection', 'reflection_requires_approval']);
        });
    }
};

