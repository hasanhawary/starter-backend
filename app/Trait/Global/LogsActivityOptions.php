<?php
namespace App\Trait\Global;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsActivityOptions
{
    use LogsActivity;

    /**
     * Configure the activity log options for the model.
     *
     * This method sets the default logging behavior for the model by:
     * - Logging all attributes.
     * - Logging only the attributes that have been changed.
     * - Using the class name as the log name.
     * - Preventing the submission of empty logs.*
     * @return LogOptions
     */

    public function getActivitylogOptions(): LogOptions
    {
        $logOptions= LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName(class_basename($this))
            ->dontSubmitEmptyLogs();

        if (property_exists($this, 'logExceptAttributes')) {
            $logOptions->logExcept($this->logExceptAttributes);
        }

        return $logOptions;
    }

}
