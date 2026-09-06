<?php

namespace App\Models;

use App\Enum\Commercial\DeploymentModeEnum;
use App\Enum\Commercial\DeploymentStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends BaseModel
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'mode',
        'status',
        'health_status',
        'current_version',
        'target_release_id',
        'last_health_check_at',
        'infrastructure_reference',
        'metadata',
    ];

    protected $casts = [
        'mode' => DeploymentModeEnum::class,
        'status' => DeploymentStatusEnum::class,
        'last_health_check_at' => 'datetime',
        'infrastructure_reference' => 'array',
        'metadata' => 'array',
    ];

    public static array $basicOperations = [
        'manage-deployments',
        'view-fleet',
        'provision-clients',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function targetRelease(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'target_release_id');
    }

    public function provisioningLogs(): HasMany
    {
        return $this->hasMany(ProvisioningLog::class);
    }

    public function provisioningTokens(): HasMany
    {
        return $this->hasMany(ProvisioningToken::class);
    }
}
