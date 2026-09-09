<?php

namespace Modules\Form\Tools\Form\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Form\app\Models\FormField;
use Modules\Form\app\Models\FormSubmission;
use Modules\Form\app\Models\FormSubmissionValue;
use Modules\Form\Tools\Form\Contracts\FormSubmissionServiceInterface;
use Throwable;

class FormSubmissionService implements FormSubmissionServiceInterface
{
    /**
     * @throws Throwable
     */
    public function createSubmission(array $data): FormSubmission
    {
        return DB::transaction(function () use ($data): FormSubmission {
            $sourceModel = resolveModel($data['source_type'], $data['source_module'] ?? null);

            $submission = FormSubmission::create([
                'source_id' => $data['source_id'],
                'form_id' => $data['form_id'],
                'source_type' => $sourceModel->getMorphClass(),
                'submission_id' => $data['submission_id'],
                'submission_type' => $data['submission_type'],
            ]);

            $values = $this->prepareValuesForCreation($data['fields']);

            $submission->values()->createMany($values);

            return $submission;
        });
    }

    /**
     * @throws Throwable
     */
    public function updateSubmission(Model $source, array $data): Model
    {
        return DB::transaction(function () use ($source, $data): Model {
            $this->updateExistingValues($source, $data);

            return $source;
        });
    }

    public function getValidationRulesForFields(array $fieldIds): array
    {
        $rules = [];
        $formFieldIds = collect($fieldIds)->pluck('form_field_id')->filter();

        if ($formFieldIds->isEmpty()) {
            return $rules;
        }

        $formFields = FormField::whereIn('id', $formFieldIds)
            ->get()
            ->keyBy('id');

        foreach ($fieldIds as $index => $field) {
            $formFieldId = $field['form_field_id'] ?? null;

            if ($formFieldId && isset($formFields[$formFieldId])) {
                $scheme = $formFields[$formFieldId]->scheme;

                if (isset($scheme['rules']) && is_array($scheme['rules'])) {
                    $rules["fields.{$index}.value"] = $scheme['rules'];
                }
            }
        }

        return $rules;
    }

    public function getValidationAttributesForSubmission(array $fieldIds): array
    {
        $attributes = [];

        $formFieldIds = collect($fieldIds)
            ->pluck('form_field_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($formFieldIds)) {
            return $attributes;
        }

        $formFields = FormField::whereIn('id', $formFieldIds)
            ->pluck('name', 'id');

        foreach ($fieldIds as $index => $field) {
            if (isset($field['form_field_id']) && $formFields->has($field['form_field_id'])) {
                $attributes["fields.{$index}.value"] = $formFields[$field['form_field_id']];
            }
        }

        return $attributes;
    }

    protected function prepareValuesForCreation(array $fields): array
    {
        $formFieldIds = collect($fields)->pluck('form_field_id')->filter();

        $formFields = FormField::whereIn('id', $formFieldIds)
            ->get()
            ->keyBy('id');

        return collect($fields)->map(function (array $field) use ($formFields): array {
            $formFieldId = $field['form_field_id'] ?? null;
            $formField = $formFields[$formFieldId] ?? null;

            return [
                'form_field_id' => $formFieldId,
                'form_step_id' => $formField?->form_step_id,
                'value' => $field['value'] ?? null,
            ];
        })->all();
    }

    private function updateExistingValues(Model $source, array $data): void
    {
        $submission = FormSubmission::where('source_id', $source->getKey())
            ->where('source_type', $source->getMorphClass())
            ->latest()
            ->first();

        if (! $submission) {
            return;
        }

        foreach ($data['fields'] as $field) {
            FormSubmissionValue::where('form_submission_id', $submission->id)
                ->where('form_field_id', $field['form_field_id'])
                ->update(['value' => $field['value'] ?? null]);
        }
    }
}
