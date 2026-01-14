<?php

namespace App\Models\Central;

use App\Models\Admin;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends \Spatie\Multitenancy\Models\Tenant
{
    use HasUuids, SoftDeletes, CreatedByObserver;

    protected $keyType = 'string';
    public $incrementing = false;

    public bool $inPermission = true;
    public array $specialOperations = ['toggle-active'];

    protected $fillable = [
        'name',
        'domain',
        'database',
        'is_active',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
