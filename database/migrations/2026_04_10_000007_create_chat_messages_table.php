<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->enum('status', ['complete', 'loading', 'error'])->default('complete');
            $table->json('controls')->nullable();
            $table->text('revised_prompt')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['chat_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
