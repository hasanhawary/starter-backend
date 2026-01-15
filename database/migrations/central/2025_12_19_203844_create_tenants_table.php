<?php

use App\Enum\Tenant\TenantStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('database')->unique();

            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->string('status')->default(TenantStatusEnum::default())->comment(TenantStatusEnum::commentFormat());
            $table->foreignId('created_by')->constrained('admins')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
