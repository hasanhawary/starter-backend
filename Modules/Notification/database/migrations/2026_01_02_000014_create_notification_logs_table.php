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
        Schema::create('notification_logs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('notification_template_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('notifiable');
            $table->string('channel');
            $table->json('payload');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->index(['notifiable_type', 'notifiable_id', 'status']);
            $table->index('sent_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
