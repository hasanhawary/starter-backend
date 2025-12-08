<?php

namespace App\Rules;

use App\Services\Global\QueryHelper;
use Closure;
use App\Exceptions\ModelAlreadyExistsException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

class UniqueWithTrashed implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param string $modelClass
     * @param string $resourceClass
     */
    public function __construct(protected string $modelClass, protected string $resourceClass, protected ?int $ignoreId = null)
    {
    }

    /**
     * Validate the given value.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @throws ModelAlreadyExistsException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $model = $this->modelClass::withTrashed()
            ->when($this->ignoreId, fn($q) => $q->where('id', '!=', $this->ignoreId))
            ->where(function (Builder $query) use ($attribute, $value) {
                is_array($value)
                    ? QueryHelper::applyJsonSearch($query, $attribute, $value, true)
                    : $query->orWhere($attribute, $value);
            })->first();

        if ($model) {
            throw new ModelAlreadyExistsException([
                'item' => new $this->resourceClass($model),
                'type' => $this->modelClass,
            ], __('validation.already_exists'), 433);
        }
    }
}
