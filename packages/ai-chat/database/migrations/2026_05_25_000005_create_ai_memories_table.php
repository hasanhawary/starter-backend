<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_memories', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('content');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_memories');
    }
};
