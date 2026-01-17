<?php

namespace App\Models\Central;

use App\Models\BaseModel;
use App\Tools\Subscription\Scopes\PlanScopes;
use App\Tools\Subscription\Traits\HasPlanMethods;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class Plan extends BaseModel
{
    use CreatedByObserver, HasTranslations, HasPlanMethods, PlanScopes, LogsActivityOptions, SoftDeletes;

    public bool $inPermission = true;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public array $translatable = ['name'];

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

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

}
