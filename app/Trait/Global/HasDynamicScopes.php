<?php

namespace App\Trait\Global;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

trait HasDynamicScopes
{
    /**
     * Apply dynamic query scopes based on request input.
     *
     * @param  array<string, string>|null  $scopeMap
     */
    public function applyScopesFilter(Builder $query): static
    {
        $scopes = Arr::wrap(request('scopes'));
        $values = Arr::wrap(request('values'));

        foreach ($scopes as $scope) {
            $method = 'scope'.ucfirst($scope);

            if (! method_exists($query->getModel(), $method)) {
                continue;
            }

            $params = $values[$this->scopeMap[$scope] ?? $scope] ?? null;

            if ($params !== null) {
                $query->{$scope}($params);
            } else {
                $query->{$scope}();
            }
        }

        return $this;
    }
}
