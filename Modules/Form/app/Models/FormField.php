<?php

namespace Modules\Form\app\Models;

use App\Models\User;
use App\Trait\Global\CreatedByObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Translatable\HasTranslations;

class FormField extends Model
{
    use CreatedByObserver, HasTranslations, LogsActivity, SoftDeletes;

    public bool $inPermission = true;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'scheme', 'form_step_id', 'created_by',
        'status',
    ];

    protected $casts = [
        'scheme' => 'array',
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

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(FormStep::class);
    }
}
