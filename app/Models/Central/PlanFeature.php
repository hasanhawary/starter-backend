<?php

namespace App\Models\Central;

use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

class PlanFeature extends BaseModel
{
    use CreatedByObserver, LogsActivityOptions, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'plan_id',
        'name',
        'key',
        'value',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean'
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
