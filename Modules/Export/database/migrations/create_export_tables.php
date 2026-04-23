<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Export\App\Enum\ExportStatusEnum;

return new class extends Migration
{
    private mixed $creatorModel = null;

    public function __construct()
    {
        $this->creatorModel = config('export.creator_model');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('export_files', function (Blueprint $table) {
            $table->id();
            $table->string('exportable_type');
            $table->unsignedBigInteger('exportable_id')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('format')->default('excel');
            $table->string('status')->default(ExportStatusEnum::Pending->value)->comment(ExportStatusEnum::commentFormat());
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained((new $this->creatorModel)->getTable())->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained((new $this->creatorModel)->getTable())->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['exportable_type', 'exportable_id', 'created_by']);
            $table->index(['created_by', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_files');
    }
};
