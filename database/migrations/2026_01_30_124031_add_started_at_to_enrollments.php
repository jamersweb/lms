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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('enrolled_at');
            $table->index('started_at');
        });

        // Backfill: set started_at = enrolled_at for existing enrollments
        DB::statement('UPDATE enrollments SET started_at = enrolled_at WHERE started_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['started_at']);
            $table->dropColumn('started_at');
        });
    }
};
