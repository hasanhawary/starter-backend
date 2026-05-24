<?php

namespace App\Rules;

use App\Exceptions\ModelAlreadyExistsException;
use App\Services\Global\QueryHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class UniqueCheck implements ValidationRule
{
    public function __construct(
        protected string $modelClass,
        protected string $resourceClass,
        protected ?string $ignoreId = null
    ) {}

    /**
     * @throws ModelAlreadyExistsException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $model = $this->buildQuery($attribute, $value)->first();

        if (! $model) {
            return;
        }

        throw new ModelAlreadyExistsException([
            'item' => new $this->resourceClass($model),
            'type' => $this->modelClass,
        ], __('validation.already_exists'), 433);
    }

    protected function buildQuery(string $attribute, mixed $value): Builder
    {
        $query = $this->modelClass::query();

        if ($this->supportsSoftDeletes()) {
            $query->withTrashed();
        }

        $query->when(
            filled($this->ignoreId),
            fn (Builder $q) => $q->whereKeyNot($this->ignoreId)
        );

        $query->where(function (Builder $q) use ($attribute, $value) {
            if (is_array($value)) {
                QueryHelper::applyJsonSearch($q, $attribute, $value, true);
            } else {
                $q->where($attribute, $value);
            }
        });

        return $query;
    }

    protected function supportsSoftDeletes(): bool
    {
        return in_array(
            SoftDeletes::class,
            class_uses_recursive($this->modelClass),
            true
        );
    }
}
