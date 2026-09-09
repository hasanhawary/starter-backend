<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Schedule events follow the record they were raised for: they are hidden
     * while that record is soft deleted and come back with it on restore, so
     * the table needs its own `deleted_at` rather than a hard delete.
     */
    public function up(): void
    {
        Schema::table('schedule_events', static function (Blueprint $table) {
            if (! Schema::hasColumn('schedule_events', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_events', static function (Blueprint $table) {
            if (Schema::hasColumn('schedule_events', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
