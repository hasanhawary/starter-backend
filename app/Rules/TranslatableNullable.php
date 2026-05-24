<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;

class TranslatableNullable implements ValidationRule
{
    protected array $languageRules;

    protected array $attributeLabels;

    public function __construct(
        protected string $table,
        protected array $rules = [], // ['sometimes','nullable']
        protected ?string $route = null,
    ) {
        $this->languageRules = config('lang.languages_validation', []);
        $this->attributeLabels = $this->flattenAttributes(trans('validation.attributes'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->languageRules as $lang => $requirement) {
            $key = "$attribute.$lang";
            $val = $value[$lang] ?? null;
            $rulesForLang[] = 'sometimes';
            $rulesForLang[] = 'nullable';

            $validator = Validator::make([
                $attribute => [$lang => $val],
            ], [
                "$attribute.$lang" => $rulesForLang,
            ], [], $this->attributeLabels);

            if ($validator->fails()) {
                foreach ($validator->errors()->get($key) as $error) {
                    $fail($error);
                }
            }
        }
    }

    protected function flattenAttributes(array $attributes): array
    {
        $flattened = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $flattened["$key.$subKey"] = $subValue;
                }
            } else {
                $flattened[$key] = $value;
            }
        }

        return $flattened;
    }
}
