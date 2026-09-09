<?php

namespace Modules\Form\app\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Modules\Form\app\Models\Form;

/**
 * Adds the polymorphic many-to-many relation to {@see Form} for any model that
 * can be linked to forms through the `form_related` pivot table.
 */
trait HasFormRelations
{
    /**
     * Active forms related to this model.
     */
    public function forms(): MorphToMany
    {
        return $this->allForms()->wherePivot('is_active', true);
    }

    /**
     * The single active form related to this model, if any.
     */
    public function form(): Model
    {
        return $this->forms()->first();
    }

    /**
     * Every form related to this model, regardless of the active state.
     */
    public function allForms(): MorphToMany
    {
        return $this->morphToMany(Form::class, 'related', 'form_related', 'related_id', 'form_id')
            ->withPivot('is_active');
    }

    /**
     * Activate the given form for this model and deactivate any previously
     * related form.
     */
    public function syncForm(int $formId): void
    {
        $this->allForms()
            ->newPivotStatement()
            ->where('related_id', $this->getKey())
            ->where('related_type', $this->getMorphClass())
            ->update(['is_active' => false]);

        $this->allForms()->syncWithoutDetaching([
            $formId => ['is_active' => true],
        ]);
    }
}
