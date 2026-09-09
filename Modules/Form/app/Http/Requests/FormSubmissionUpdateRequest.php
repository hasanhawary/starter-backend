<?php

namespace Modules\Form\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use Modules\Form\Tools\Form\Facades\Form;
use Throwable;

class FormSubmissionUpdateRequest extends BaseFormRequest
{
    /**
     * @throws Throwable
     */
    public function rules(): array
    {
        $fields = $this->input('fields', []);

        $rules = [
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
        return Form::getValidationAttributesForSubmission($this->input('fields', []));
    }
}
