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
        Schema::create('reminders_setting', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_event_id')->constrained('notification_events')->onDelete('cascade');
            $table->string('channel', 50)->nullable()->comment('sms, email, push, reminder, calendar, notification');
            $table->enum('offset_unit', ['hour', 'day', 'minute']);
            $table->string('offset_type');
            $table->integer('offset_value');
            $table->foreignId('reminder_based_on_column')->constrained('notification_verifiable_dates')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders_setting');
    }
};
