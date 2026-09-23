<?php

namespace Modules\Showcase\app\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use App\Trait\Global\HasDeletedBy;
use App\Trait\Global\HasDeleteMethods;
use App\Trait\Global\LogsActivityOptions;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Showcase\app\Trait\HasShowcaseNotes;
use Modules\Showcase\Database\Factories\ShowcaseCategoryFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Grouping for showcase records. Self-referencing, so it also demonstrates a
 * parent/children relation on one table.
 */
class ShowcaseCategory extends BaseModel
{
    use CreatedByObserver, HasDeletedBy, HasFactory, HasShowcaseNotes, HasTranslations, LogsActivityOptions, SoftDeletes;

    public array $translatable = ['name', 'description'];

    public bool $inPermission = true;

    public array $specialOperations = ['force-delete', 'restore', 'toggle-active'];

    protected $fillable = [
        'name',
        'description',
        'code',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function setIconAttribute($value): void
    {
        $path = Media::replace($this->attributes['icon'] ?? null)->upload($value, 'showcase/categories');
        $this->attributes['icon'] = $path;
    }

    public function icon(): Attribute
    {
        return Attribute::make(get: static fn ($value) => Media::url($value));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper methods
    |--------------------------------------------------------------------------
    */
    /**
     * Relations that block deleting a category, read by
     * {@see HasDeleteMethods::guardLinkedRelations()}.
     *
     * @return array<int|string, string>
     */
    public function preventDeleteRelations(): array
    {
        return ['showcases', 'children'];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function showcases(): HasMany
    {
        return $this->hasMany(Showcase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */
    protected static function newFactory(): Factory
    {
        return ShowcaseCategoryFactory::new();
    }
}
