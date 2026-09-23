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
        Schema::create('showcase_tags', function (Blueprint $table) {
            $table->id();
            $table->mediumText('name');
            $table->string('slug')->unique();
            $table->string('color', 9)->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('showcase_tags');
    }
};
