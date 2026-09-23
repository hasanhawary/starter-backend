<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Showcase\app\Enum\ShowcaseNoteTypeEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('showcase_notes', function (Blueprint $table) {
            $table->id();
            // Polymorphic owner: any model using the HasShowcaseNotes trait.
            // The table is the record's whole timeline — the notes people write
            // and the status transitions the workflow records.
            $table->morphs('notable');
            $table->string('type')->default(ShowcaseNoteTypeEnum::default());
            $table->text('body');

            // Only a `status_change` row fills these: the transition it recorded.
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_pinned')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['notable_type', 'notable_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showcase_notes');
    }
};
