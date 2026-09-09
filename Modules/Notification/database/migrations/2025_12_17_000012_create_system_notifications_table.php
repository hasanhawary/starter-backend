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
        Schema::create('system_notifications', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->onDelete('cascade');
            $table->string('title');
            $table->text('body');
            $table->json('meta')->nullable();
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->morphs('notifiable');
            $table->timestamp('read_at')->nullable();
            $table->foreignId('notification_template_id')->constrained('notification_templates')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};

