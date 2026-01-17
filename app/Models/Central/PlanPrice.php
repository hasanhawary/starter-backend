<?php

namespace App\Models\Central;

use App\Enum\Subscription\PlanCycleEnum;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\BaseModel;

class PlanPrice extends BaseModel
{
    use CreatedByObserver, LogsActivityOptions;

    protected $fillable = [
        'plan_id',
        'cycle',
        'price',
        'currency',
        'discount_percent',
    ];

    protected $casts = [
        'cycle' => PlanCycleEnum::class,
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
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
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function getDiscountedPrice(): float
    {
        if (!$this->discount_percent) {
            return (float) $this->price;
        }

        return (float) ($this->price - ($this->price * $this->discount_percent / 100));
    }

    public function getFormattedPrice(): string
    {
        return $this->currency . ' ' . number_format($this->price, 2);
    }

    public function getFormattedDiscountedPrice(): string
    {
        return $this->currency . ' ' . number_format($this->getDiscountedPrice(), 2);
    }

    public function getCycleLabel(): string
    {
        return $this->cycle->label();
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
