<?php

namespace Modules\Form\Tools\Form\Builders;

use Illuminate\Database\Eloquent\Model;
use Modules\Form\app\Models\Form;
use Modules\Form\app\Models\FormSubmission;
use Modules\Form\Tools\Form\Contracts\FormBuilderInterface;
use Modules\Form\Tools\Form\Services\FormService;
use Modules\Form\Tools\Form\Services\FormSubmissionService;
use Throwable;

/**
 * Form Builder - Facade for form and submission operations.
 *
 * This class acts as a simplified interface for creating, updating,
 * and managing forms and their submissions. It delegates operations
 * to the appropriate service classes.
 */
class FormBuilder implements FormBuilderInterface
{
    /**
     * Create a new FormBuilder instance.
     */
    public function __construct(
        protected FormService $formService,
        protected FormSubmissionService $formSubmissionService
    ) {}

    /**
     * Create a new form.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createForm(array $data): Form
    {
        return $this->formService->createForm($data);
    }

    /**
     * Update an existing form.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateForm(Form $form, array $data): Form
    {
        return $this->formService->updateForm($form, $data);
    }

    /**
     * Update form status (active/inactive).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateFormStatus(Form $form, array $data): Form
    {
        return $this->formService->updateForm($form, $data);
    }

    /**
     * Submit a form with data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function submitForm(array $data): FormSubmission
    {
        return $this->formSubmissionService->createSubmission($data);
    }

    /**
     * Update an existing form submission.
     *
     * @param  array<string, mixed>  $data
     * @return FormSubmission
     *
     * @throws Throwable
     */
    public function updateSubmission(Model $source, array $data): Model
    {
        return $this->formSubmissionService->updateSubmission($source, $data);
    }

    /**
     * Get validation rules for form submission fields.
     *
     * @param  array<int, int>  $fieldIds
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function getValidationRulesForSubmission(array $fieldIds): array
    {
        return $this->formSubmissionService->getValidationRulesForFields($fieldIds);
    }

    /**
     * Get validation attributes for form submission fields.
     *
     * @param  array<int, int>  $fieldIds
     * @return array<string, string>
     *
     * @throws Throwable
     */
    public function getValidationAttributesForSubmission(array $fieldIds): array
    {
        return $this->formSubmissionService->getValidationAttributesForSubmission($fieldIds);
    }

    /**
     * Get all available validation rules configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValidationRules(): array
    {
        return $this->formService->getValidationRules();
    }
}
