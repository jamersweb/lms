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
        Schema::create('whatsapp_trigger_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 100);
            $table->string('template_name', 100)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('phone', 40);
            $table->text('message');
            $table->string('language', 10)->default('en');
            $table->json('context')->nullable();
            $table->string('status', 20)->default('pending'); // pending|processing|sent|failed
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('claimed_at')->nullable();
            $table->string('claimed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('external_id')->nullable();
            $table->text('error_message')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['event_key', 'created_at']);
            $table->index('user_id');
            $table->unique('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_trigger_events');
    }
};

