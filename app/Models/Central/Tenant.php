<?php

namespace App\Models\Central;

use App\Enum\Tenant\TenantStatusEnum;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LdapRecord\Models\Relations\HasMany;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Tenant extends \Spatie\Multitenancy\Models\Tenant
{
    use HasUuids, SoftDeletes, CreatedByObserver, UsesLandlordConnection;

    protected $keyType = 'string';
    public $incrementing = false;

    public bool $inPermission = true;
    public array $specialOperations = ['force-delete', 'restore', 'toggle-active'];

    protected $fillable = [
        'name',
        'domain',
        'database',
        'status',
        'is_active',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status' => TenantStatusEnum::class,
        'settings' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'tenant_id');
    }
}
