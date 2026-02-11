<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
                $table->string('title_en_roman')->nullable()->after('title_en');
                $table->string('title_ur')->nullable()->after('title_en_roman');
            }

            if (!Schema::hasColumn('lessons', 'summary_en')) {
                $table->text('summary_en')->nullable()->after('duration_seconds');
                $table->text('summary_en_roman')->nullable()->after('summary_en');
                $table->text('summary_ur')->nullable()->after('summary_en_roman');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'title_en')) {
                $table->dropColumn(['title_en', 'title_en_roman', 'title_ur']);
            }

            if (Schema::hasColumn('lessons', 'summary_en')) {
                $table->dropColumn(['summary_en', 'summary_en_roman', 'summary_ur']);
            }
        });
    }
};

