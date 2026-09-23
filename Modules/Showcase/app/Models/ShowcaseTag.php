<?php

namespace Modules\Showcase\app\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Showcase\Database\Factories\ShowcaseTagFactory;
use Spatie\Translatable\HasTranslations;

/**
 * Free-form label attached to showcase records through a pivot that carries its
 * own `is_primary` payload. Intentionally has no soft deletes: not every model
 * in a module needs the same lifecycle.
 */
class ShowcaseTag extends BaseModel
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public bool $inPermission = true;

    public array $specialOperations = ['toggle-active'];

    protected $fillable = [
        'name',
        'slug',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes && Casts methods
    |--------------------------------------------------------------------------
    */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function showcases(): BelongsToMany
    {
        return $this->belongsToMany(Showcase::class, 'showcase_showcase_tag')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */
    protected static function newFactory(): Factory
    {
        return ShowcaseTagFactory::new();
    }
}
