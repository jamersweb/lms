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
        Schema::create('ask_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ask_thread_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['user', 'mentor']);
            $table->text('body');
            $table->timestamps();

            $table->foreign('ask_thread_id')
                ->references('id')
                ->on('ask_threads')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ask_messages');
    }
};

