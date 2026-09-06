<?php

namespace App\Models;

use App\Enum\Commercial\ProvisioningStepEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisioningLog extends BaseModel
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'deployment_id',
        'step',
        'status', // started, completed, failed, skipped
        'error_message',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'step' => ProvisioningStepEnum::class,
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \RuntimeException('ProvisioningLogs are immutable and cannot be updated.');
        });

        static::deleting(function () {
            throw new \RuntimeException('ProvisioningLogs are immutable and cannot be deleted.');
        });
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
