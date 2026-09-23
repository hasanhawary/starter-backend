<?php

namespace Modules\Form\app\Models;

use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;
use Spatie\Activitylog\Support\LogOptions;
// use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Form extends Model
{
    use CreatedByObserver, HasTranslations, SoftDeletes;

    public bool $inPermission = true;

    public array $specialOperations = ['restore', 'force-delete'];

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'description', 'created_by', 'is_active', 'has_steps', 'status', 'version'];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_active' => 'boolean',
        'has_steps' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
   |--------------------------------------------------------------------------
   | Helper methods
   |--------------------------------------------------------------------------
   */
    /**
     * Activate the given related ids (all sharing the same type). When
     * $deactivateExisting is true any previously related record is deactivated
     * first; otherwise existing active records are kept untouched.
     *
     * @param  array<int, int>  $relatedIds
     */
    public function syncRelated(string $relatedType, array $relatedIds, bool $deactivateExisting = true): void
    {
        if ($deactivateExisting) {
            $this->related()->update(['is_active' => false]);
        }

        foreach ($relatedIds as $relatedId) {
            $this->related()->updateOrCreate(
                ['related_type' => $relatedType, 'related_id' => (int) $relatedId],
                ['is_active' => true],
            );
        }
    }

    /**
     * Get every related entity (Cause, CauseRequest, Project, ...) resolved
     * from the polymorphic `related.module` relation as a single collection.
     */
    public function getRelatedItemsAttribute(): SupportCollection
    {
        return $this->related
            ->loadMissing('module')
            ->pluck('module')
            ->filter()
            ->values();
    }

    public function syncSteps($steps): Collection
    {
        foreach ($steps as $step) {
            $newStep = $this->steps()->create($step);
            $newStep->syncFields($step['fields']);
        }

        return $this->steps;
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

    public function fields(): HasManyThrough
    {
        return $this->hasManyThrough(FormField::class, FormStep::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FormStep::class)->orderBy('sorting_order');
    }

    public function lastStep(): HasOne
    {
        return $this->hasOne(FormStep::class)->latestOfMany();
    }

    public function related(): HasMany
    {
        return $this->hasMany(FormRelated::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
