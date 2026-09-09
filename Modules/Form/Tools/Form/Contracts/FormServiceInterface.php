<?php

namespace Modules\Form\Tools\Form\Contracts;

use Modules\Form\app\Models\Form;
use Throwable;

interface FormServiceInterface
{
    /**
     * Create a new form with or without steps.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createForm(array $data): Form;

    /**
     * Update an existing form or create a new version if it has submissions.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateForm(Form $form, array $data): Form;

    /**
     * Get validation rules configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValidationRules(): array;
}
