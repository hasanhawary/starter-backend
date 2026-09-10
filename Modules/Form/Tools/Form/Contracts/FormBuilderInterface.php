<?php

namespace Modules\Form\Tools\Form\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Form\app\Models\Form;
use Modules\Form\app\Models\FormSubmission;
use Throwable;

/**
 * Form Builder Interface.
 *
 * Defines the contract for form building and submission operations.
 * This interface acts as a facade for managing forms, submissions,
 * and their associated validation rules.
 */
interface FormBuilderInterface
{
    /**
     * Create a new form.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createForm(array $data): Form;

    /**
     * Update an existing form.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateForm(Form $form, array $data): Form;

    /**
     * Submit a form with data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function submitForm(array $data): FormSubmission;

    /**
     * Update an existing form submission.
     *
     * @param  array<string, mixed>  $data
     * @return FormSubmission
     *
     * @throws Throwable
     */
    public function updateSubmission(Model $source, array $data): Model;

    /**
     * Get validation rules for form submission fields.
     *
     * @param  array<int, int>  $fieldIds
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function getValidationRulesForSubmission(array $fieldIds): array;

    /**
     * Get validation attributes for form submission fields.
     *
     * @param  array<int, int>  $fieldIds
     * @return array<string, string>
     *
     * @throws Throwable
     */
    public function getValidationAttributesForSubmission(array $fieldIds): array;

    /**
     * Get all available validation rules configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValidationRules(): array;
}
