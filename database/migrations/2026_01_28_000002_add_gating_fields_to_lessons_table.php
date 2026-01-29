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
            $table->string('allowed_gender', 10)->default('all')->after('is_free_preview');
            $table->boolean('requires_bayah')->default(false)->after('allowed_gender');
            $table->string('min_level', 20)->nullable()->after('requires_bayah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['allowed_gender', 'requires_bayah', 'min_level']);
        });
    }
};

