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
        Schema::table('dua_requests', function (Blueprint $table) {
            // Add content column (longText as per requirements)
            $table->longText('content')->nullable()->after('is_anonymous');
        });

        // Copy data from request_text to content (if table has data)
        if (DB::table('dua_requests')->count() > 0) {
            DB::statement('UPDATE dua_requests SET content = request_text');
        }

        Schema::table('dua_requests', function (Blueprint $table) {
            // Drop old column
            $table->dropColumn('request_text');

            // Add status field (active|hidden)
            $table->string('status')->default('active')->after('is_anonymous');

            // Add moderation fields
            $table->foreignId('hidden_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('hidden_at')->nullable()->after('hidden_by');

            // Add soft deletes
            $table->softDeletes();

            // Add indexes
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dua_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropSoftDeletes();
            $table->dropForeign(['hidden_by']);
            $table->dropColumn(['hidden_by', 'hidden_at', 'status']);

            // Restore request_text column
            $table->text('request_text')->after('is_anonymous');
            DB::statement('UPDATE dua_requests SET request_text = content');
            $table->dropColumn('content');
        });
    }
};
