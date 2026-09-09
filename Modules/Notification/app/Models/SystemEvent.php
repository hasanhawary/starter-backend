<?php

namespace Modules\Notification\app\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Notification\app\Enum\SystemEventModuleEnum;
use Modules\Notification\app\Enum\SystemEventSlugEnum;
use Spatie\Translatable\HasTranslations;

class SystemEvent extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    public bool $inPermission = true;

    public array $specialOperations = [];

    protected $fillable = [
        'name',
        'module',
        'model_type',
        'event_slug',
        'is_active',
        'relation_type',
        'relation',
        'access_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'name' => 'array',
        'event_slug' => SystemEventSlugEnum::class,
        'module' => SystemEventModuleEnum::class,
    ];

    public function syncVariables(mixed $variables): self
    {
        $this->variables()->sync($variables);

        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function notificationEvents(): HasMany
    {
        return $this->hasMany(NotificationEvent::class);
    }

    public function variables(): MorphToMany
    {
        return $this->morphToMany(
            Variable::class,
            'variableable',
            'variable_assignments'
        )->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
