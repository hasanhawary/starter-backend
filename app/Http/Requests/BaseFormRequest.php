<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Override;

abstract class BaseFormRequest extends FormRequest
{
    #[Override]
    protected function prepareForValidation(): void
    {
        // Convert empty values to null
        $this->replace(collect($this->all())
            ->map(fn ($value) => resolveEmptyToNull($value))
            ->toArray());
    }

    /**
     * Normalize a boolean input from front (0,1,true,false,'true','false')
     */
    protected function booleanInput(string $key, bool $default = false): bool
    {
        $value = $this->input($key, $default);

        return match (true) {
            is_bool($value) => $value,
            is_numeric($value) => (bool) $value,
            is_string($value) => in_array(strtolower($value), ['1', 'true'], true),
            default => $default,
        };
    }

    #[Override]
    protected function failedValidation(Validator $validator)
    {
        $errors = (new ValidationException($validator))->errors();
        $firstMessage = collect($errors)->flatten()->first();

        throw new HttpResponseException(response()->json([
            'message' => $firstMessage,
            'errors' => $errors,
        ], 422));
    }
}
