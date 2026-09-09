<?php

/*
|--------------------------------------------------------------------------
| Form Module Validation Messages
|--------------------------------------------------------------------------
| Message templates used by the Form module requests. Kept inside the module
| so it does not depend on the application-level `validation` translations.
*/

return [
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'unique' => 'The :attribute has already been taken.',
    'string' => 'The :attribute field must be a string.',
    'array' => 'The :attribute field must be an array.',
    'boolean' => 'The :attribute field must be true or false.',
    'numeric' => 'The :attribute field must be a number.',
    'integer' => 'The :attribute field must be an integer.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'exists' => 'The selected :attribute is invalid.',
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
];
