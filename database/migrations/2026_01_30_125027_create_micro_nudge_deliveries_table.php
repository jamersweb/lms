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
        Schema::create('micro_nudge_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('micro_nudge_campaigns')->cascadeOnDelete();
            $table->foreignId('audio_clip_id')->nullable()->constrained('audio_clips')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('whatsapp'); // future-proof
            $table->timestamp('sent_at')->nullable();
            $table->string('status')->default('queued'); // queued|sent|failed|skipped
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->string('dedupe_key')->unique(); // prevents duplicates per user per window
            $table->timestamps();

            $table->index(['campaign_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('micro_nudge_deliveries');
    }
};
