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
        Schema::table('users', function (Blueprint $table) {
            // Add whatsapp fields
            $table->string('whatsapp_number', 30)->nullable()->after('level');
            $table->boolean('whatsapp_opt_in')->default(false)->after('whatsapp_number');

            // Add last_active_at timestamp with index
            $table->timestamp('last_active_at')->nullable()->after('whatsapp_opt_in');
            $table->index('last_active_at');

            // Update level to have default value if it doesn't already
            // Note: This will only work if level column doesn't have a default yet
            // If it already has one, this will be a no-op
        });

        // Set default level for existing null values
        \DB::table('users')->whereNull('level')->update(['level' => 'beginner']);

        // Update level column to have default (if not already set)
        Schema::table('users', function (Blueprint $table) {
            $table->string('level', 20)->default('beginner')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_active_at']);
            $table->dropColumn(['whatsapp_number', 'whatsapp_opt_in', 'last_active_at']);

            // Revert level default (make nullable again)
            $table->string('level', 20)->nullable()->change();
        });
    }
};
