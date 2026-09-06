<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceActivation extends BaseModel
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'license_id',
        'device_id',
        'installation_id',
        'activation_token_hash',
        'activation_credential',
        'status',
        'activated_at',
        'last_checkin_at',
        'offline_grace_expires_at',
        'revoked_at',
        'metadata',
    ];

    protected $hidden = ['activation_token_hash', 'activation_credential'];

    protected $casts = [
        'activated_at' => 'datetime',
        'activation_credential' => 'encrypted',
        'last_checkin_at' => 'datetime',
        'offline_grace_expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
