<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_memories', function (Blueprint $table): void {
            $table->string('guest_id', 100)->nullable()->index()->after('user_id');
            $table->string('tenant_id', 100)->nullable()->index()->after('guest_id');
            $table->string('agent_id', 100)->nullable()->index()->after('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_memories', function (Blueprint $table): void {
            $table->dropColumn(['guest_id', 'tenant_id', 'agent_id']);
        });
    }
};
