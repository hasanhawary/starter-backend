<?php

namespace App\Services\Global;

class DiscoveryConfigResolver
{
    /**
     * Resolve a config value for the lookup manager.
     *
     * The `discovery` config stores each filter `label` as a translation key so
     * the config file stays boot-safe (config loads before the translator and
     * before the locale middleware). Labels are translated here instead, at
     * request time, so `help-configs` returns them in the request's locale.
     */
    public function __invoke(string $key, mixed $default = null): mixed
    {
        $value = config($key, $default);

        if ($key === 'discovery' || str_starts_with($key, 'discovery.')) {
            return $this->translateLabels($value);
        }

        return $value;
    }

    /**
     * Recursively translate every `label` entry within a config array.
     */
    private function translateLabels(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $childKey => $childValue) {
            if ($childKey === 'label' && is_string($childValue)) {
                $value[$childKey] = __($childValue);
            } elseif (is_array($childValue)) {
                $value[$childKey] = $this->translateLabels($childValue);
            }
        }

        return $value;
    }
}
