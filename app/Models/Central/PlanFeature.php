<?php

namespace App\Models\Central;

use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\BaseModel;

class PlanFeature extends BaseModel
{
    use CreatedByObserver, LogsActivityOptions;

    public bool $inPermission = true;

    protected $fillable = [
        'plan_id',
        'feature_key',
        'value',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
