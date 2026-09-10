<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger of scheduled reminder dispatches. The composite unique key makes the
     * hourly notification:reminder scan idempotent: a (setting, model, target date)
     * combination is claimed exactly once no matter how many ticks match it.
     */
    public function up(): void
    {
        Schema::create('notification_reminder_dispatches', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminders_setting_id')->constrained('reminders_setting')->cascadeOnDelete();
            $table->foreignId('notification_event_id')->constrained('notification_events')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->date('target_date');
            $table->timestamps();

            $table->unique(['reminders_setting_id', 'model_type', 'model_id', 'target_date'], 'reminder_dispatch_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reminder_dispatches');
    }
};
