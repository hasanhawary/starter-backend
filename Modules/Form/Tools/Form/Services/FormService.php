<?php

namespace Modules\Form\Tools\Form\Services;

use Illuminate\Support\Facades\DB;
use Modules\Form\app\Models\Form;
use Modules\Form\app\Models\FormRelated;
use Modules\Form\app\Models\FormStep;
use Modules\Form\Tools\Form\Contracts\FormServiceInterface;
use Throwable;

class FormService implements FormServiceInterface
{
    /**
     * @var array<string, mixed> Form validation configuration
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('form.form_validations') ?? [];
    }

    /**
     * Create a new form with or without steps.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function createForm(array $data): Form
    {
        $form = match ($data['has_steps']) {
            true => $this->createFormWithSteps($data),
            false => $this->createFormWithoutSteps($data),
        };

        if (isset($data['related_id'])) {
            $this->setFormRelated($form, $data);
        }

        return $form;
    }

    /**
     * Update an existing form or create a new version if it has submissions.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function updateForm(Form $form, array $data): Form
    {
        $hasSubmissions = $form->submissions()->exists();

        if (! $hasSubmissions) {
            $updatedForm = match ($data['has_steps']) {
                true => $this->updateFormWithSteps($form, $data),
                false => $this->updateFormWithoutSteps($form, $data),
            };

            $this->setFormRelated($updatedForm, $data);

            return $updatedForm;
        }

        $form->update(['is_active' => false]);
        $data['version'] = $form->version + 1;

        $newForm = match ($data['has_steps']) {
            true => $this->createFormWithSteps($data),
            false => $this->createFormWithoutSteps($data),
        };

        $this->transferFormRelated($form, $newForm);
        $this->setFormRelated($newForm, $data, deactivateExisting: false);

        return $newForm;
    }

    /**
     * Get validation rules configuration.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getValidationRules(): array
    {
        return $this->config;
    }

    /**
     * Build rules with schema from rule names.
     *
     * @param  array<int, string>  $ruleNames
     * @return array<int, array<string, mixed>>
     */
    private function buildRulesWithSchema(array $ruleNames): array
    {
        $rulesWithSchema = [];
        foreach ($ruleNames as $ruleName) {
            if (isset($this->config['rules_schema'][$ruleName])) {
                $rulesWithSchema[] = [
                    'name' => $ruleName,
                    'schema' => $this->config['rules_schema'][$ruleName]['schema'],
                ];
            }
        }

        return $rulesWithSchema;
    }

    /**
     * Sync steps for a form.
     *
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function syncSteps(Form $form, array $steps = []): void
    {
        $currentStepIds = $this->extractIds($steps);
        $currentSteps = $this->filterExistingItems($steps);
        $newSteps = $this->filterNewItems($steps);

        $form->steps()->whereNotIn('id', $currentStepIds)->delete();
        $form->syncSteps($newSteps);
        $this->updateSteps($form, $currentSteps);
    }

    /**
     * Update existing steps and their fields.
     *
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function updateSteps(Form $form, array $steps): void
    {
        foreach ($steps as $stepData) {
            $step = $form->steps()->find($stepData['id']);
            if ($step) {
                $step->update($stepData);
                $this->syncFields($step, $stepData['fields'] ?? []);
            }
        }
    }

    /**
     * Sync fields for a given step.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncFields(FormStep $step, array $fields): void
    {
        $currentFieldIds = $this->extractIds($fields);
        $newFields = $this->filterNewItems($fields);
        $existingFields = $this->filterExistingItems($fields);

        $step->fields()->whereNotIn('id', $currentFieldIds)->delete();
        $step->fields()->createMany($newFields);

        foreach ($existingFields as $fieldData) {
            $field = $step->fields()->find($fieldData['id']);

            if ($field) {
                $field->update($fieldData);
            }
        }
    }

    /**
     * Extract IDs from an array of items.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, int>
     */
    private function extractIds(array $items): array
    {
        return collect($items)
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Filter items that have existing IDs.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function filterExistingItems(array $items): array
    {
        return collect($items)
            ->whereNotNull('id')
            ->values()
            ->toArray();
    }

    /**
     * Filter items that are new (no ID).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function filterNewItems(array $items): array
    {
        return collect($items)
            ->whereNull('id')
            ->values()
            ->toArray();
    }

    private function transferFormRelated(Form $oldForm, Form $newForm): void
    {
        $rows = $oldForm->related()
            ->where('is_active', true)
            ->get()
            ->map(fn (FormRelated $related): array => [
                'form_id' => $newForm->getKey(),
                'related_type' => $related->related_type,
                'related_id' => $related->related_id,
                'is_active' => true,
            ])->all();

        if ($rows !== []) {
            FormRelated::query()->insert($rows);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function setFormRelated(Form $form, array $data, bool $deactivateExisting = true): void
    {
        $relatedType = $data['related_type'] ?? null;
        $relatedIds = $data['related_id'] ?? [];

        $relatedModule = $data['related_module'] ?? null;
        $resolvedModel = resolveModel($relatedType, $relatedModule);

        if (! $resolvedModel || $relatedIds === []) {
            return;
        }

        $form->syncRelated($resolvedModel->getMorphClass(), (array) $relatedIds, $deactivateExisting);
    }

    /**
     * Create a form with steps.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    private function createFormWithSteps(array $data): Form
    {
        return DB::transaction(function () use ($data) {
            $steps = $data['steps'] ?? [];
            $form = Form::create($data);
            $form->syncSteps($steps);

            return $form;
        });
    }

    /**
     * Create a form without steps.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    private function createFormWithoutSteps(array $data): Form
    {
        return DB::transaction(function () use ($data) {
            $form = Form::create($data);
            $form->lastStep->fields()->createMany($data['fields'] ?? []);

            return $form;
        });
    }

    /**
     * Update a form with steps.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    private function updateFormWithSteps(Form $form, array $data): Form
    {
        return DB::transaction(function () use ($data, $form) {
            $form->update($data);
            $this->syncSteps($form, $data['steps'] ?? []);

            return $form;
        });
    }

    /**
     * Update a form without steps.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    private function updateFormWithoutSteps(Form $form, array $data): Form
    {
        return DB::transaction(function () use ($data, $form) {
            $form->update($data);
            $this->syncFields($form->lastStep, $data['fields'] ?? []);

            return $form;
        });
    }
}
