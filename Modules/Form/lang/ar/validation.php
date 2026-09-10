<?php

/*
|--------------------------------------------------------------------------
| Form Module Validation Messages
|--------------------------------------------------------------------------
| Message templates used by the Form module requests. Kept inside the module
| so it does not depend on the application-level `validation` translations.
*/

return [
    'required' => ':attribute مطلوب',
    'required_if' => ':attribute مطلوب في حال ما إذا كان :other يساوي :value',
    'required_with' => ':attribute مطلوب إذا توفّر :values',
    'unique' => 'قيمة :attribute مُستخدمة من قبل',
    'string' => 'يجب أن يكون :attribute نصًا',
    'array' => 'يجب أن يكون :attribute مصفوفة',
    'boolean' => 'يجب أن تكون قيمة :attribute إما صحيحة أو خاطئة',
    'numeric' => 'يجب أن يكون :attribute رقمًا',
    'integer' => 'يجب أن يكون :attribute عددًا صحيحًا',
    'distinct' => 'قيمة :attribute مُكرّرة',
    'exists' => 'قيمة :attribute غير موجودة',
    'max' => [
        'string' => 'يجب أن لا يتجاوز طول النّص :attribute :max حروفٍ/حرفًا',
    ],
];
