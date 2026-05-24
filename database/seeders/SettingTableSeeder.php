<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Starting brand settings seeder...');

        $template = config('brands.template');
        $brand = brandName();

        if (empty($template)) {
            $this->command->error('❌ No template found.');

            return;
        }

        $brandValues = $this->loadBrandValues($brand);

        foreach ($template as $key => $value) {
            if (is_array($value)) {
                $this->storeSettings($value, $key, $brandValues);
            }
        }

        $this->command->info('✅ Brand settings seeded successfully!');
    }

    /**
     * Load brand values file
     */
    private function loadBrandValues(string $brand): array
    {
        $path = database_path("seeders/brands/{$brand}.php");

        return file_exists($path) ? require $path : [];
    }

    /**
     * Recursive storage (parent + nested groups ONLY)
     * SAME BEHAVIOR AS OLD SEEDER
     */
    private function storeSettings(
        array $settings,
        string $key,
        array $brandValues,
        ?string $groupPrefix = null
    ): void {
        // Build group EXACTLY like old seeder
        $group = $groupPrefix
            ? $groupPrefix.'.'.$key
            : $key;

        // Numeric array = settings list
        if (! $this->isAssoc($settings)) {
            foreach ($settings as $item) {
                if (! isset($item['key'])) {
                    continue;
                }

                Setting::updateOrCreate(
                    [
                        'key' => $item['key'],
                        'group' => $group,
                    ],
                    [
                        'value' => $this->getBrandValue($group, $item['key'], $brandValues),
                        'type' => $item['type'] ?? 'text',
                        'label' => $item['label'] ?? null,
                        'placeholder' => $item['placeholder'] ?? null,
                        'is_multi_lang' => $item['is_multi_lang'] ?? false,
                    ]
                );
            }

            return;
        }

        // Assoc array = nested groups
        foreach ($settings as $childKey => $childValue) {
            if (is_array($childValue)) {
                $this->storeSettings(
                    $childValue,
                    $childKey,
                    $brandValues,
                    $group
                );
            }
        }
    }

    /**
     * Get value from brand file (if exists)
     */
    private function getBrandValue(
        string $group,
        string $key,
        array $brandValues
    ): mixed {
        $parts = explode('.', $group);

        $section = $parts[0] ?? null;
        $groupKey = $parts[1] ?? null;

        if (! $section || ! $groupKey) {
            return null;
        }

        foreach ($brandValues[$section][$groupKey] ?? [] as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item['value'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check associative array
     */
    private function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
