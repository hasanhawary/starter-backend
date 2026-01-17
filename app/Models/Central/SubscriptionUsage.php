<?php

namespace App\Models\Central;

use App\Models\BaseModel;
use App\Tools\Subscription\Traits\HasSubscriptionUsageMethods;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class SubscriptionUsage extends BaseModel
{
    use CreatedByObserver, LogsActivityOptions, HasSubscriptionUsageMethods;

    protected $table = 'subscription_usage';

    protected $fillable = [
        'tenant_id',
        'key',
        'used_value',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'used_value' => 'integer',
    ];

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
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
