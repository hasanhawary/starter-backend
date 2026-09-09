<?php

namespace Modules\Form\Tools\Form\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Form\app\Models\FormSubmission;
use Throwable;

interface FormSubmissionServiceInterface
{
    /**
     * Create a form submission with its field values.
     *
     * @param  array  $data  The submission data containing fields and metadata
     *
     * @throws Throwable
     */
    public function createSubmission(array $data): FormSubmission;

    /**
     * Update an existing form submission's field values.
     *
     * @param  Model  $source  The submission source to update
     * @param  array  $data  The submission data to update
     *
     * @throws Throwable
     */
    public function updateSubmission(Model $source, array $data): Model;

    /**
     * Get validation rules for specified form fields.
     *
     * @param  array  $fieldIds  Array of field data containing form_field_id keys
     * @return array Validation rules indexed by field path (e.g., 'fields.0.value')
     */
    public function getValidationRulesForFields(array $fieldIds): array;

    /**
     * Get validation attributes (human-readable names) for form submission fields.
     *
     * @param  array  $fieldIds  Array of field data containing form_field_id keys
     * @return array Attribute names indexed by field path (e.g., 'fields.0.value' => 'Email')
     */
    public function getValidationAttributesForSubmission(array $fieldIds): array;
}
