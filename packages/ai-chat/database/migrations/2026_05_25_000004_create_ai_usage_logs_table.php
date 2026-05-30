<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('conversation_id', 36)->nullable()->index();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->integer('input_tokens')->default(0);
            $table->integer('output_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->integer('latency_ms')->nullable();
            $table->string('status', 50)->default('success');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['provider', 'model']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
