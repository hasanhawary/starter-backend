<?php

namespace App\Models\Central;

use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\LogsActivityOptions;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use HasanHawary\MediaManager\Facades\Media;


class Product extends Model
{
    use HasTranslations, CreatedByObserver, LogsActivityOptions;

    public bool $inPermission = true;
    public array $translatable = ['name', 'description'];
    protected $fillable = ['name', 'description', 'phone', 'photo', 'content', 'status', 'country_id'];

    /*
     |--------------------------------------------------------------------------
     | Casts && Set Custom Attributes
     |--------------------------------------------------------------------------
     */
     public function photo(): Attribute
    {
        return new Attribute(
            get: fn($value) => Media::url($value),
            set: fn($value) => Media::replace($this->$column ?? null)->upload($value)
        );
    }

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

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
