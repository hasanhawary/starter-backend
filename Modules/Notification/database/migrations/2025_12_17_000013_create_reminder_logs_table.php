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
        Schema::create('reminder_logs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_event_id')->nullable()->constrained('schedule_events')->nullOnDelete();
            $table->foreignId('notification_event_id')->nullable()->constrained('notification_events')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel', 50)->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('pending')->comment('pending, sent, failed');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->index(['user_id', 'status']);
            $table->index('sent_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};

