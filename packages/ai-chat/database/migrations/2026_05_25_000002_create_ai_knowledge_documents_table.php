<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('title', 255);
            $table->string('source_path', 500);
            $table->string('source_type', 50);
            $table->string('content_hash', 64)->unique();
            $table->integer('chunk_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_documents');
    }
};
