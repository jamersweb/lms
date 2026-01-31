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
        Schema::create('micro_nudge_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->string('schedule_type')->default('hourly'); // hourly|daily|cron_like
            $table->integer('send_hour')->nullable(); // for daily schedule (0-23)
            $table->integer('send_minute')->default(0); // minute of hour
            $table->string('timezone')->nullable(); // default app timezone
            $table->string('rotation')->default('random'); // random|sequence
            $table->json('audience_filters')->nullable(); // {min_level, requires_bayah, gender}
            $table->json('clip_ids')->nullable(); // list of audio_clip ids (curated playlist)
            $table->unsignedBigInteger('last_sent_clip_id')->nullable(); // for sequence rotation
            $table->timestamps();

            $table->index('is_enabled');
            $table->index('schedule_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('micro_nudge_campaigns');
    }
};
