<?php

namespace Modules\Form\app\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Rules\TranslatableNullable;
use App\Rules\TranslatableRequired;

class FormRequest extends BaseFormRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                new TranslatableRequired('forms', ['string', 'max:191'], 'form'),
            ],
            'description' => ['nullable', 'array', new TranslatableNullable('forms', ['string'], 'form')],
            'is_active' => 'sometimes|boolean',
            'has_steps' => 'required|boolean',

            'fields' => 'required_if:has_steps,false|array',
            'fields.*.id' => 'sometimes|nullable|exists:form_fields,id',
            'fields.*.name' => [
                'required',
                'array',
                new TranslatableNullable('form_fields', ['string', 'max:191'], 'form_fields'),
            ],
            'fields.*.scheme' => 'required|array',

            'steps' => 'required_if:has_steps,true|array',
            'steps.*.name' => [
                'required_if:has_steps,true',
                'array',
                new TranslatableNullable('form_steps', ['string', 'max:191'], 'form_steps'),
            ],
            'steps.*.sorting_order' => 'required_if:has_steps,true|numeric',
            'steps.*.id' => 'sometimes|nullable|exists:form_steps,id',
            'steps.*.fields' => 'required_if:has_steps,true|array',
            'steps.*.fields.*.id' => 'sometimes|nullable|exists:form_fields,id',
            'steps.*.fields.*.name' => [
                'required_if:has_steps,true',
                'array',
                new TranslatableNullable('form_fields', ['string', 'max:191'], 'form_fields'),
            ],
            'steps.*.fields.*.scheme' => 'required|array',
            'related_type' => 'nullable|required_with:related_id|string',
            'related_id' => 'nullable|required_with:related_type|array',
            'related_id.*' => 'integer|distinct',
            'related_module' => 'nullable|string',
        ];
    }

    /**
     * Module scoped attribute names, so Laravel's default messages do not fall
     * back to the application level `attributes` translations.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('form::attributes.name'),
            'name.ar' => __('form::attributes.name_ar'),
            'name.en' => __('form::attributes.name_en'),
            'description' => __('form::attributes.description'),
            'description.ar' => __('form::attributes.description_ar'),
            'description.en' => __('form::attributes.description_en'),
            'is_active' => __('form::attributes.is_active'),
            'has_steps' => __('form::attributes.has_steps'),

            'steps' => __('form::attributes.steps'),
            'steps.*.name' => __('form::attributes.step_name'),
            'steps.*.sorting_order' => __('form::attributes.step_sorting_order'),
            'steps.*.fields' => __('form::attributes.fields'),
            'steps.*.fields.*.name' => __('form::attributes.field_name'),
            'steps.*.fields.*.scheme' => __('form::attributes.field_scheme'),

            'fields' => __('form::attributes.fields'),
            'fields.*.name' => __('form::attributes.field_name'),
            'fields.*.scheme' => __('form::attributes.field_scheme'),

            'related_id' => __('form::attributes.related_id'),
            'related_type' => __('form::attributes.related_type'),
            'related_module' => __('form::attributes.related_module'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('form::validation.required', ['attribute' => __('form::attributes.name')]),

            'name.ar.required' => __('form::validation.required', ['attribute' => __('form::attributes.name_ar')]),
            'name.ar.unique' => __('form::validation.unique', ['attribute' => __('form::attributes.name_ar')]),

            'name.en.unique' => __('form::validation.unique', ['attribute' => __('form::attributes.name_en')]),

            'name.ar.string' => __('form::validation.string', ['attribute' => __('form::attributes.name_ar')]),
            'name.ar.max' => __('form::validation.max.string', ['attribute' => __('form::attributes.name_ar'), 'max' => 191]),

            'name.en.string' => __('form::validation.string', ['attribute' => __('form::attributes.name_en')]),
            'name.en.max' => __('form::validation.max.string', ['attribute' => __('form::attributes.name_en'), 'max' => 191]),

            'description.en.max' => __('form::validation.max.string', ['attribute' => __('form::attributes.description_en'), 'max' => 1500]),
            'description.ar.max' => __('form::validation.max.string', ['attribute' => __('form::attributes.description_ar'), 'max' => 1500]),

            'steps.required' => __('form::validation.required', ['attribute' => __('form::attributes.steps')]),
            'steps.array' => __('form::validation.array', ['attribute' => __('form::attributes.steps')]),
            'steps.*.name.required_if' => __('form::validation.required_if', ['attribute' => __('form::attributes.step_name'), 'other' => __('form::attributes.has_steps')]),
            'steps.*.sorting_order.required_if' => __('form::validation.required_if', ['attribute' => __('form::attributes.step_sorting_order'), 'other' => __('form::attributes.has_steps')]),
            'steps.*.key.required_if' => __('form::validation.required_if', ['attribute' => __('form::attributes.step_key'), 'other' => __('form::attributes.has_steps')]),

            'fields.required_if' => __('form::validation.required_if', ['attribute' => __('form::attributes.fields'), 'other' => __('form::attributes.has_steps')]),
            'fields.array' => __('form::validation.array', ['attribute' => __('form::attributes.fields')]),
            'fields.*.name.required' => __('form::validation.required', ['attribute' => __('form::attributes.field_name')]),
            'fields.*.scheme.required' => __('form::validation.required', ['attribute' => __('form::attributes.field_scheme')]),
            'steps.*.fields.required_if' => __('form::validation.required_if', ['attribute' => __('form::attributes.fields'), 'other' => __('form::attributes.has_steps')]),
            'steps.*.fields.array' => __('form::validation.array', ['attribute' => __('form::attributes.fields')]),
            'steps.*.fields.*.name.required' => __('form::validation.required', ['attribute' => __('form::attributes.field_name')]),

            'related_id.required_with' => __('form::validation.required_with', ['attribute' => __('form::attributes.related_id'), 'values' => __('form::attributes.related_type')]),
            'related_type.required_with' => __('form::validation.required_with', ['attribute' => __('form::attributes.related_type'), 'values' => __('form::attributes.related_id')]),
        ];
    }
}
