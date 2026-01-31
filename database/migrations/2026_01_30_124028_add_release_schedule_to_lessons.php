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
            $table->timestamp('release_at')->nullable()->after('sort_order');
            $table->integer('release_day_offset')->nullable()->after('release_at');

            // Indexes for querying
            $table->index('release_at');
            $table->index('release_day_offset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['release_at']);
            $table->dropIndex(['release_day_offset']);
            $table->dropColumn(['release_at', 'release_day_offset']);
        });
    }
};
