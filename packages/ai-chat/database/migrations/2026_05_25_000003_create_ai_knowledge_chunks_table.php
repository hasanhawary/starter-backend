<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('document_id', 36);
            $table->foreign('document_id')->references('id')->on('ai_knowledge_documents')->cascadeOnDelete();
            $table->text('content');
            $table->integer('chunk_index');
            $table->json('embedding')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
    }
};
