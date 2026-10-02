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
        Schema::table('moderation_actions', function (Blueprint $table) {
            $table->enum('action', ['lock', 'unlock', 'delete', 'restore', 'warn', 'ban', 'hide', 'unhide'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'hide' and 'unhide' from the action enum
        Schema::table('moderation_actions', function (Blueprint $table) {
            $table->enum('action', ['lock', 'unlock', 'delete', 'restore', 'warn', 'ban'])->change();
        });
    }
};
