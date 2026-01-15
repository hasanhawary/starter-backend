<?php

namespace App\Models\Central;

use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\BaseModel;

class SubscriptionUsage extends BaseModel
{
    use CreatedByObserver, LogsActivityOptions;

    public bool $inPermission = true;

    protected $table = 'subscription_usage';

    protected $fillable = [
        'tenant_id',
        'feature_key',
        'used_value',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    /*
     |--------------------------------------------------------------------------
     | Casts && Set Custom Attributes
     |--------------------------------------------------------------------------
     */

    /*
    |--------------------------------------------------------------------------
    | Activity log methods
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly($this->fillable);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(tenant::class, 'tenant_id');
    }
}
