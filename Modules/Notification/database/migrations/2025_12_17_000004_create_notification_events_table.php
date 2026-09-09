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
        Schema::create('notification_events', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_event_id')->constrained('system_events')->onDelete('cascade');
            $table->json('name')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('model_type')->nullable();
            $table->boolean('is_reminder')->default(false);
            $table->json('sent_types')->nullable();
            $table->unsignedBigInteger('recipientable_id')->nullable();
            $table->string('recipientable_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_events');
    }
};

