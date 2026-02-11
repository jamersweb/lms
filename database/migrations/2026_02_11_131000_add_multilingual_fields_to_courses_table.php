<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'title_en')) {
                $table->string('title_en')->nullable()->after('title');
                $table->string('title_en_roman')->nullable()->after('title_en');
                $table->string('title_ur')->nullable()->after('title_en_roman');
            }

            if (!Schema::hasColumn('courses', 'description_en')) {
                $table->text('description_en')->nullable()->after('description');
                $table->text('description_en_roman')->nullable()->after('description_en');
                $table->text('description_ur')->nullable()->after('description_en_roman');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'title_en')) {
                $table->dropColumn(['title_en', 'title_en_roman', 'title_ur']);
            }

            if (Schema::hasColumn('courses', 'description_en')) {
                $table->dropColumn(['description_en', 'description_en_roman', 'description_ur']);
            }
        });
    }
};

