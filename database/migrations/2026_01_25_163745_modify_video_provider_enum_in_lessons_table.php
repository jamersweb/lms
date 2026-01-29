<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL requires ALTER TABLE to modify ENUM.
        // SQLite (used in tests) does not support MODIFY COLUMN / ENUM.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE lessons MODIFY COLUMN video_provider ENUM('youtube', 'mp4', 'external', 'vimeo') DEFAULT 'youtube'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE lessons MODIFY COLUMN video_provider ENUM('youtube', 'mp4') DEFAULT 'youtube'");
    }
};
