<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chat_message_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->longText('url');
            $table->string('alt');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->text('prompt');
            $table->text('revised_prompt');
            $table->unsignedBigInteger('seed');
            $table->json('palette')->nullable();
            $table->string('format', 20)->default('png');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_images');
    }
};
