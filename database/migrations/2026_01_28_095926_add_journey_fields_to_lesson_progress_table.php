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
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->string('status', 20)->default('locked')->after('is_completed');
            $table->timestamp('available_at')->nullable()->after('completed_at');
            $table->timestamp('unlocked_at')->nullable()->after('available_at');
            $table->timestamp('started_at')->nullable()->after('unlocked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['status', 'available_at', 'unlocked_at', 'started_at']);
        });
    }
};
