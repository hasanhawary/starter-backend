<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user receipt / verification for a scheduled notification or calendar row.
     * The parent schedule_events row keeps morph receiver (primary); this table supports
     * read/verified state and future multi-recipient flows without duplicating the event row.
     */
    public function up(): void
    {
        Schema::create('schedule_event_receivers', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_event_id')->constrained('schedule_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('verified_at')->nullable()->comment('User acknowledged / verified the reminder');
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(['schedule_event_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_event_receivers');
    }
};
