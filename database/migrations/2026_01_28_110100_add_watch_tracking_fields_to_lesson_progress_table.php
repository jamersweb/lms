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
            $table->integer('time_watched_seconds')->default(0)->after('last_position_seconds');
            $table->timestamp('last_heartbeat_at')->nullable()->after('time_watched_seconds');
            $table->float('max_playback_rate_seen')->default(1)->after('last_heartbeat_at');
            $table->boolean('seek_detected')->default(false)->after('max_playback_rate_seen');
            $table->boolean('verified_completion')->default(false)->after('seek_detected');
            $table->timestamp('verified_at')->nullable()->after('verified_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn([
                'time_watched_seconds',
                'last_heartbeat_at',
                'max_playback_rate_seen',
                'seek_detected',
                'verified_completion',
                'verified_at',
            ]);
        });
    }
};

