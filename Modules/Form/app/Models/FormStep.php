<?php

namespace Modules\Form\app\Models;

use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Translatable\HasTranslations;

class FormStep extends Model
{
    use CreatedByObserver, HasTranslations, LogsActivity, SoftDeletes;

    public bool $inPermission = true;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'sorting_order', 'created_by', 'form_id'];

    protected $casts = [
        'name' => 'array',
    ];

    /*
   |--------------------------------------------------------------------------
   | Activity logs
   |--------------------------------------------------------------------------
   */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logOnly(array_merge($this->fillable, ['name']));
    }

    public function syncFields(array $fields): Collection
    {
        return $this->fields()->createMany($fields);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function submissionValues(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class, 'form_step_id');
    }
}
