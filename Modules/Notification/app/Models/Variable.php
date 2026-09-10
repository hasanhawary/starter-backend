<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

class Variable extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];

    public array $specialOperations = [];

    protected $fillable = [
        'model_type',
        'access_key',
        'type',
        'enum_class',
        'name',
        'module',
        'relation_type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations Methods
    |--------------------------------------------------------------------------
    */
    public function systemEvent(): MorphToMany
    {
        return $this->morphedByMany(
            SystemEvent::class,
            'variableable',
            'variable_assignments',
            'variable_id',
            'variableable_id'
        )->withTimestamps();
    }

    public function notificationEvent(): MorphToMany
    {
        return $this->morphedByMany(
            NotificationEvent::class,
            'variableable',
            'variable_assignments',
            'variable_id',
            'variableable_id'
        )->withTimestamps();
    }
}
