<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class License extends BaseModel
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    public bool $inPermission = true;

    public array $basicOperations = ['read'];

    public array $specialOperations = ['change-plan', 'update-limits', 'renew', 'update-status'];

    protected $fillable = [
        'organization_id',
        'key_hash',
        'key_fingerprint',
        'key_last_four',
        'plan_id',
        'plan',
        'plan_code',
        'plan_version',
        'status',
        'max_devices',
        'max_branches',
        'starts_at',
        'expires_at',
        'grace_period_days',
        'features',
        'entitlement_overrides',
        'limit_overrides',
        'metadata',
    ];

    protected $hidden = ['key_hash', 'key_fingerprint'];

    protected $casts = [
        'plan_version' => 'integer',
        'max_devices' => 'integer',
        'max_branches' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'grace_period_days' => 'integer',
        'features' => 'array',
        'entitlement_overrides' => 'array',
        'limit_overrides' => 'array',
        'metadata' => 'array',
    ];

    public function commercialPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function planModel(): ?Plan
    {
        return $this->commercialPlan;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(DeviceActivation::class);
    }

    public function isActiveAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->status === 'active'
            && (! $this->starts_at || $this->starts_at->lessThanOrEqualTo($at))
            && (! $this->expires_at || $this->expires_at->greaterThan($at));
    }
}
