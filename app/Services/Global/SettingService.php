<?php

namespace App\Services\Global;

use App\Models\Setting;
use HasanHawary\MediaManager\Facades\Media;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    protected string $cacheKeyPrefix = 'settings_';

    /**
     * Get all settings as nested associative array, cached.
     */
    public function all(): array
    {
        $brand = brandName();

        return Cache::rememberForever($this->cacheKeyPrefix.$brand, function () {
            $settings = Setting::all();

            $nested = [];
            foreach ($settings as $setting) {
                $keys = explode('.', $setting->group ?: 'general'); // group path
                $current = &$nested;

                foreach ($keys as $key) {
                    if (! isset($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }

                // Store the actual value + meta
                $current[$setting->key] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'is_multi_lang' => $setting->is_multi_lang,
                    'placeholder' => $setting->placeholder,
                    'label' => $setting->label,
                ];
            }

            return $nested;
        });
    }

    /**
     * Get a setting value by group.key path
     */
    public function get(string $path, $lang = null, $default = null)
    {
        $settings = $this->all();
        $keys = explode('.', $path);
        $current = $settings;

        foreach ($keys as $key) {
            if (! isset($current[$key])) {
                return $default;
            }

            $current = $current[$key];
        }

        // If leaf is a setting item, return 'value'
        if (! empty($current['value'])) {
            if (is_array($current['value'])) {
                // Return value for the requested language if exists, otherwise return the whole array
                return $current['value'][$lang] ?? $current['value'];
            }

            // Not an array, return the value directly
            return $current['value'];
        }

        // Fallback to default
        return $default;

    }

    /**
     * Clear settings cache
     */
    public function clearCache(): void
    {
        $brand = brandName();
        Cache::forget($this->cacheKeyPrefix.$brand);
    }

    /**
     * Update multiple settings with media handling and env sync
     *
     * @throws \JsonException
     */
    public function updateSettings(array $settings): void
    {
        foreach ($settings as $item) {
            $setting = Setting::where('key', $item['key'])
                ->where('group', $item['group'])
                ->first();

            if (! $setting) {
                continue;
            }

            $setting->value = $this->normalizeValue($item['value'], $setting->type);
            $setting->save();

            if ($setting->is_env) {
                $this->syncEnv($item['value']);
            }
        }

        $this->clearCache();
    }

    /**
     * Normalize setting value based on type
     */
    protected function normalizeValue(mixed $value, $type): mixed
    {
        if ($value && \in_array($type, ['imageUploader', 'file'], true)) {
            return Media::replace($value)->upload($value, 'settings');
        }

        return $value;
    }

    /**
     * Sync setting value to .env file
     */
    protected function syncEnv(mixed $value): void
    {
        updateDotEnv([
            \strtoupper($value['key']) => \is_array($value)
                ? \json_encode($value, JSON_THROW_ON_ERROR)
                : $value,
        ]);
    }
}
