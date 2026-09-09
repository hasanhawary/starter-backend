<?php

namespace Modules\Form\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Modules\Form\Tools\Form\Facades\Form;
use Throwable;

class FormSubmissionRequest extends BaseFormRequest
{
    /**
     * @throws Throwable
     */
    public function rules(): array
    {
        $fields = $this->input('fields', []);

        $rules = [
            'source_type' => ['required', 'string'],
            'source_module' => ['nullable', 'string'],
            'form_id' => ['required', 'exists:forms,id'],
            'source_id' => ['required', 'integer'],
            'fields' => ['required', 'array'],
            'fields.*.form_field_id' => ['required', 'integer', 'exists:form_fields,id'],
            'fields.*.value' => ['nullable'],
        ];

        $dynamicRules = Form::getValidationRulesForSubmission($fields);

        foreach ($dynamicRules as $key => $fieldRules) {
            $rules[$key] = is_array($fieldRules)
                ? implode('|', $fieldRules)
                : $fieldRules;
        }

        return $rules;
    }

    /**
     * @throws Throwable
     */
    public function attributes(): array
    {
        $fields = $this->input('fields', []);

        return Form::getValidationAttributesForSubmission($fields);
    }
}
