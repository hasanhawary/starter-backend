<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class CommercialLicenseEvent extends BaseModel
{
    public bool $inPermission = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'license_id',
        'device_activation_id',
        'event',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::updating(function (): void {
            throw new RuntimeException('CommercialLicenseEvent records are append-only and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('CommercialLicenseEvent records are immutable audit entries and cannot be deleted.');
        });
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function deviceActivation(): BelongsTo
    {
        return $this->belongsTo(DeviceActivation::class);
    }
}
