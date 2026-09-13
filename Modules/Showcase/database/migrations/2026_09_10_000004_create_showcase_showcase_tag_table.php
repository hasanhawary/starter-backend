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
        Schema::create('showcase_showcase_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showcase_id')->constrained('showcases')->cascadeOnDelete();
            $table->foreignId('showcase_tag_id')->constrained('showcase_tags')->cascadeOnDelete();
            // Pivot payload, so the relation is read with withPivot() rather than as a bare link.
            $table->boolean('is_primary')->default(0);
            $table->timestamps();

            $table->unique(['showcase_id', 'showcase_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showcase_showcase_tag');
    }
};
