<?php

namespace Modules\Notification\app\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class NotificationVerifiableDate extends Model
{
    use HasTranslations;

    protected $table = 'notification_verifiable_dates';

    public array $translatable = ['name'];

    protected $fillable = [
        'module',
        'model_type',
        'access_key',
        'type',
        'relation',
        'name',
        'is_active',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }
}


