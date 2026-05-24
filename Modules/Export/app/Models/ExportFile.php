<?php

namespace Modules\Export\App\Models;

use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\HasDeletedBy;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Export\App\Enum\ExportFormatEnum;
use Modules\Export\App\Enum\ExportStatusEnum;
use Modules\Export\App\Scopes\ExportFileScopes;

class ExportFile extends Model
{
    use CreatedByObserver, ExportFileScopes, HasDeletedBy, SoftDeletes;

    public bool $inPermission = true;

    public array $basicOperations = ['create', 'delete'];

    public array $specialOperations = ['view-all', 'view-own', 'force-delete', 'restore'];

    protected $fillable = [
        'exportable_id',
        'exportable_type',
        'created_by',
        'file_name',
        'file_path',
        'format',
        'status',
        'started_at',
        'completed_at',
        'metadata',
        'error_message',
    ];

    /*
     |--------------------------------------------------------------------------
     | Casts && Set Custom Attributes
     |--------------------------------------------------------------------------
    */
    protected $casts = [
        'format' => ExportFormatEnum::class,
        'status' => ExportStatusEnum::class,
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function filePath(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => Media::url($value)
        );
    }

    /*
     |--------------------------------------------------------------------------
     | Helper methods
     |--------------------------------------------------------------------------
    */
    public function isReady(): bool
    {
        return $this->status === ExportStatusEnum::Completed && ! empty($this->attributes['file_path']);
    }

    /*
     |--------------------------------------------------------------------------
     | Relations methods
     |--------------------------------------------------------------------------
    */
    public function exportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
