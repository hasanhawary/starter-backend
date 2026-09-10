<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add 'name' to notification_events
        Schema::table('notification_events', static function (Blueprint $table) {
            if (! Schema::hasColumn('notification_events', 'name')) {
                $table->json('name')->nullable()->after('system_event_id');
            }
        });

        // 2) Add 'channel' to reminders_setting
        Schema::table('reminders_setting', static function (Blueprint $table) {
            if (! Schema::hasColumn('reminders_setting', 'channel')) {
                $table->string('channel', 50)->nullable()->after('notification_event_id')
                    ->comment('sms, email, push, reminder, calendar, notification');
            }
        });

        // 3) Drop 'notification_template_id' from schedule_events
        Schema::table('schedule_events', static function (Blueprint $table) {
            if (Schema::hasColumn('schedule_events', 'notification_template_id')) {
                $table->dropForeign(['notification_template_id']);
                $table->dropColumn('notification_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_events', static function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_events', 'notification_template_id')) {
                $table->foreignId('notification_template_id')
                    ->nullable()
                    ->constrained('notification_templates')
                    ->nullOnDelete();
            }
        });

        Schema::table('reminders_setting', static function (Blueprint $table) {
            if (Schema::hasColumn('reminders_setting', 'channel')) {
                $table->dropColumn('channel');
            }
        });

        Schema::table('notification_events', static function (Blueprint $table) {
            if (Schema::hasColumn('notification_events', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};

