<?php

namespace Modules\Form\app\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
// use Spatie\Activitylog\Models\Concerns\LogsActivity;

class FormSubmissionValue extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_submission_id',
        'form_field_id',
        'form_step_id',
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

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(FormStep::class, 'form_step_id');
    }
}
