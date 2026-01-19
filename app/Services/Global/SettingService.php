<?php

namespace App\Services\Global;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    protected string $cacheKeyPrefix = 'settings_';

    /**
     * Get all settings as nested associative array, cached.
     */
    public function all(): array
    {
        $brand = config('brands.default_brand');

        return Cache::rememberForever($this->cacheKeyPrefix . $brand, function () {
            $settings = Setting::all();

            $nested = [];
            foreach ($settings as $setting) {
                $keys = explode('.', $setting->group ?: 'general'); // group path
                $current = &$nested;

                foreach ($keys as $key) {
                    if (!isset($current[$key])) $current[$key] = [];
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
            if (!isset($current[$key])) {
                return $default;
            }

            $current = $current[$key];
        }

        // If leaf is a setting item, return 'value'
        if (!empty($current['value'])) {
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
     * Get a multi-lang setting value
     */
    public function getLang(string $path, ?string $lang = null, $default = null)
    {
        $lang ??= app()->getLocale(); // default language

        $setting = $this->get($path, []);
        if (!is_array($setting)) return $setting;

        // If it's multi-lang stored as array ['en' => '...', 'ar' => '...']
        return $setting[$lang] ?? $default ?? $setting['value'] ?? null;
    }

    /**
     * Clear settings cache
     */
    public function clearCache()
    {
        $brand = config('brands.default_brand');
        Cache::forget($this->cacheKeyPrefix . $brand);
    }
}
