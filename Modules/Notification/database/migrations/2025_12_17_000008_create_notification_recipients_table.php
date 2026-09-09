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
        Schema::create('notification_recipients', static function (Blueprint $table) {
            $table->id();
            $table->morphs('recipientable', 'notif_recipients_recipientable_index');
            $table->enum('type', ['relation', 'user', 'role']);
            $table->foreignId('notification_event_id')->constrained('notification_events')->CascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
};
