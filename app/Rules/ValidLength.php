<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class ValidLength implements ValidationRule
{
    protected ?int $length;

    public function __construct(protected ?int $referenceId, protected string $modelClass, protected string $lengthColumn = 'length')
    {
        $this->length = null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! class_exists($this->modelClass) || ! is_subclass_of($this->modelClass, Model::class)) {
            $fail(__('validation.invalid_model'));

            return;
        }

        $model = $this->modelClass::find($this->referenceId);

        if ($model && $model->{$this->lengthColumn}) {
            $this->length = $model->{$this->lengthColumn};

            if (strlen($value) !== (int) $this->length) {
                $fail(__('validation.exact_length', [
                    'length' => $this->length,
                    'attribute' => __('validation.attributes.phone'),
                ]));
            }
        }
    }
}
