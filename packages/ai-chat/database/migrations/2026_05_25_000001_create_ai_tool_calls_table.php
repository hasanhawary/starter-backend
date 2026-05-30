<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tool_calls', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36)->index();
            $table->string('message_id', 36)->nullable()->index();
            $table->string('tool_name', 255);
            $table->json('arguments')->nullable();
            $table->json('result')->nullable();
            $table->string('status', 50)->default('pending');
            $table->integer('duration_ms')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('tenant_id', 100)->nullable()->index();
            $table->timestamps();

            $table->index(['tool_name', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tool_calls');
    }
};
