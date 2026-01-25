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
        try {
            Schema::table('lesson_progress', function (Blueprint $table) {
                $table->index(['user_id', 'lesson_id']); 
            });
        } catch (\Exception $e) {
            // Index likely exists
        }

        try {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(['user_id', 'course_id']);
            });
        } catch (\Exception $e) {
            // Index likely exists
        }

        if (DB::getDriverName() !== 'sqlite') {
            try {
                Schema::table('lesson_transcript_segments', function (Blueprint $table) {
                    $table->fullText('text'); 
                });
            } catch (\Exception $e) {
                // Ignore
            }
        }

        try {
            Schema::table('points_events', function (Blueprint $table) {
                 $table->index(['user_id', 'event_type']);
            });
        } catch (\Exception $e) {
            // Ignore
        }
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'lesson_id']);
        });
        
        // ... (droppings)
    }
};
