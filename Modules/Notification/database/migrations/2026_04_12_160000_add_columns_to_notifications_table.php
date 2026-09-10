<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add custom columns to Laravel's notifications table
        Schema::table('notifications', static function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'notification_event_id')) {
                $table->foreignId('notification_event_id')->nullable()->after('type')
                    ->constrained('notification_events')->nullOnDelete();
            }
            if (! Schema::hasColumn('notifications', 'notification_template_id')) {
                $table->foreignId('notification_template_id')->nullable()->after('notification_event_id')
                    ->constrained('notification_templates')->nullOnDelete();
            }
            if (! Schema::hasColumn('notifications', 'channel')) {
                $table->string('channel', 50)->nullable()->after('data');
            }
            if (! Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('channel');
            }
            if (! Schema::hasColumn('notifications', 'body')) {
                $table->text('body')->nullable()->after('title');
            }
            if (! Schema::hasColumn('notifications', 'meta')) {
                $table->json('meta')->nullable()->after('body');
            }
            if (! Schema::hasColumn('notifications', 'status')) {
                $table->string('status', 30)->default('sent')->after('meta');
            }
            if (! Schema::hasColumn('notifications', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
            if (! Schema::hasColumn('notifications', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('read_at');
            }
        });

        // Drop the redundant notifications_user table
        Schema::dropIfExists('notifications_user');
    }

    public function down(): void
    {
        Schema::table('notifications', static function (Blueprint $table) {
            $columns = ['notification_event_id', 'notification_template_id', 'channel', 'title', 'body', 'meta', 'status', 'error_message', 'sent_at'];
            $existing = array_filter($columns, fn ($col) => Schema::hasColumn('notifications', $col));

            if (Schema::hasColumn('notifications', 'notification_event_id')) {
                $table->dropForeign(['notification_event_id']);
            }
            if (Schema::hasColumn('notifications', 'notification_template_id')) {
                $table->dropForeign(['notification_template_id']);
            }
            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};

