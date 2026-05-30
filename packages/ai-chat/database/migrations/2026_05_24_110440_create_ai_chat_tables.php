<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    public function up(): void
    {
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        Schema::create($conversationsTable, function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('session_id', 100)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('title')->default('New Chat');
            $table->json('metadata')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['session_id', 'updated_at']);
            $table->index(['ip_address', 'created_at']);
        });

        Schema::create($messagesTable, function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36)->index();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('agent');
            $table->string('role', 25);
            $table->text('content');
            $table->text('attachments')->nullable();
            $table->text('tool_calls')->nullable();
            $table->text('tool_results')->nullable();
            $table->text('usage')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at'], 'conversation_messages_index');
            $table->index(['session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('ai.conversations.tables.messages', 'agent_conversation_messages'));
        Schema::dropIfExists(config('ai.conversations.tables.conversations', 'agent_conversations'));
    }
};
