<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Spatie\Activitylog\LogOptions;

class PlanFeature extends Model
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
