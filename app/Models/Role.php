<?php

namespace App\Models;

use App\Scopes\User\RoleScopes;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Translatable\HasTranslations;

class Role extends SpatieRole
{
    use CreatedByObserver, HasTranslations, RoleScopes;

    public bool $inPermission = true;

    public array $basicOperations = ['create', 'update', 'delete'];

    public array $specialOperations = ['view-all', 'view-own', 'toggle-active'];

    public array $translatable = ['display_name'];

    protected $fillable = [
        'name', 'guard_name', 'display_name', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roleUsers(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }
}
