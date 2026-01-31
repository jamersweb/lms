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
        // Add 'hide' and 'unhide' to the action enum
        DB::statement("ALTER TABLE moderation_actions MODIFY COLUMN action ENUM('lock', 'unlock', 'delete', 'restore', 'warn', 'ban', 'hide', 'unhide')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'hide' and 'unhide' from the action enum
        DB::statement("ALTER TABLE moderation_actions MODIFY COLUMN action ENUM('lock', 'unlock', 'delete', 'restore', 'warn', 'ban')");
    }
};
