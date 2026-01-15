<?php

namespace App\Models\Central;

use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use App\Models\BaseModel;

class Plan extends BaseModel
{
    use CreatedByObserver, LogsActivityOptions;

    public bool $inPermission = true;

    protected $fillable = [
        'code',
        'name',
        'price',
        'billing_cycle',
        'max_users',
        'max_storage_mb',
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

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
