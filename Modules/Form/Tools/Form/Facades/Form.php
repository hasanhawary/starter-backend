<?php

namespace Modules\Form\Tools\Form\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\Form\app\Models\Form as FormModel;
use Modules\Form\app\Models\FormSubmission;
use Modules\Form\Tools\Form\Builders\FormBuilder;

/**
 * Form Facade.
 *
 * @method static FormModel createForm(array $data)
 * @method static FormModel updateForm(FormModel $form, array $data)
 * @method static FormModel updateFormStatus(FormModel $form, array $data)
 * @method static FormSubmission submitForm(array $data)
 * @method static FormSubmission updateSubmission(FormSubmission $formSubmission, array $data)
 * @method static array getValidationRulesForSubmission(array $fieldIds)
 * @method static array getValidationAttributesForSubmission(array $fieldIds)
 * @method static array getValidationRules()
 *
 * @see FormBuilder
 */
class Form extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'form';
    }
}
