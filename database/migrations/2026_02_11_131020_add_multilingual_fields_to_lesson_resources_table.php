<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            if (!Schema::hasColumn('lesson_resources', 'sunnah_pointers_en')) {
                $table->text('sunnah_pointers_en')->nullable()->after('sunnah_pointers');
                $table->text('sunnah_pointers_en_roman')->nullable()->after('sunnah_pointers_en');
                $table->text('sunnah_pointers_ur')->nullable()->after('sunnah_pointers_en_roman');
            }

            if (!Schema::hasColumn('lesson_resources', 'duas_text_en')) {
                $table->text('duas_text_en')->nullable()->after('duas_text');
                $table->text('duas_text_en_roman')->nullable()->after('duas_text_en');
                $table->text('duas_text_ur')->nullable()->after('duas_text_en_roman');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lesson_resources', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_resources', 'sunnah_pointers_en')) {
                $table->dropColumn(['sunnah_pointers_en', 'sunnah_pointers_en_roman', 'sunnah_pointers_ur']);
            }

            if (Schema::hasColumn('lesson_resources', 'duas_text_en')) {
                $table->dropColumn(['duas_text_en', 'duas_text_en_roman', 'duas_text_ur']);
            }
        });
    }
};

