<?php

namespace Modules\Form\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class FormSubmission extends Model
{
    use LogsActivity, SoftDeletes;

    public bool $inPermission = true;

    protected $fillable = [
        'submission_type',
        'submission_id',
        'source_id',
        'source_type',
        'form_id',
        'value',
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
            ->logOnly($this->fillable);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations methods
    |--------------------------------------------------------------------------
    */

    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class, 'form_submission_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function submission(): MorphTo
    {
        return $this->morphTo();
    }
}
