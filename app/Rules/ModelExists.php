<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelExists implements ValidationRule
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $wheres
     */
    public function __construct(
        protected string $modelClass,
        protected string $column = 'id',
        protected array $wheres = [],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_subclass_of($this->modelClass, Model::class)) {
            throw new \InvalidArgumentException("{$this->modelClass} must be an Eloquent Model.");
        }

        $model = new $this->modelClass;
        $query = $model->query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $query->whereNull($model->getDeletedAtColumn());
        }

        $exists = $query
            ->when($this->wheres !== [], fn ($query) => $query->where($this->wheres))
            ->where($this->column, $value)
            ->exists();

        if (! $exists) {
            $fail(__('validation.exists'));
        }
    }
}
