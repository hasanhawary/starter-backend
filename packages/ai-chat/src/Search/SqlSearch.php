<?php

namespace AiChat\Search;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SqlSearch
{
    public function search(string $query, string $modelClass, int $limit = 10): array
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        $model = new $modelClass;
        $searchableFields = $this->getSearchableFields($model);

        if (empty($searchableFields)) {
            return [];
        }

        $modelQuery = $modelClass::where(function ($q) use ($searchableFields, $query) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$query}%");
            }
        });

        if (method_exists($model, 'scopeForSearch')) {
            $modelQuery->forSearch();
        }

        return $modelQuery->limit($limit)->get()->all();
    }

    protected function getSearchableFields(Model $model): array
    {
        $fillable = $model->getFillable();

        if (empty($fillable)) {
            return [];
        }

        $stringTypes = ['string', 'text'];

        return array_filter($fillable, function ($field) use ($model) {
            $cast = $model->getCasts()[$field] ?? null;

            if ($cast !== null) {
                return in_array($cast, ['string', 'text']);
            }

            return Str::contains($field, ['name', 'title', 'description', 'content', 'body', 'summary', 'label']);
        });
    }
}
