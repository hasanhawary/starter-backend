<?php

namespace Modules\Form\app\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Form\app\Models\FormSubmission;

/**
 * Adds the polymorphic relation to {@see FormSubmission} for any model that can
 * act as the `source` of one or more form submissions.
 */
trait HasSubmissionRelations
{
    /**
     * Every form submission where this model is the source.
     */
    public function formSubmissions(): MorphMany
    {
        return $this->morphMany(FormSubmission::class, 'source');
    }
}
