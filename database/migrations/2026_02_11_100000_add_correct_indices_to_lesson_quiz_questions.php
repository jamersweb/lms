<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_quiz_questions', function (Blueprint $table) {
            $table->json('correct_indices')->nullable()->after('correct_index');
        });

        // Backfill: single correct_index -> correct_indices as [correct_index]
        $rows = DB::table('lesson_quiz_questions')->whereNull('correct_indices')->get();
        foreach ($rows as $row) {
            $idx = (int) $row->correct_index;
            DB::table('lesson_quiz_questions')->where('id', $row->id)->update([
                'correct_indices' => json_encode([$idx]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('lesson_quiz_questions', function (Blueprint $table) {
            $table->dropColumn('correct_indices');
        });
    }
};
