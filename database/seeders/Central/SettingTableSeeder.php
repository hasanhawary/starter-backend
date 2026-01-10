<?php

namespace Database\Seeders\Central;

use App\Models\Central\Setting;
use Illuminate\Database\Seeder;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentBrand = config('brands.default_brand', 'wakeb');
        $this->command->info("🔹 Seeder started for brand: $currentBrand");

        $brandsConfig = config('brands.brands');

        if (!isset($brandsConfig[$currentBrand])) {
            $this->command->warn("⚠️ Brand '$currentBrand' not found in config. Seeder skipped.");
            return;
        }

        $brandSettings = $brandsConfig[$currentBrand];

        $this->storeSettings($brandSettings, $groupPrefix = '');

        $this->command->info("✅ Seeder finished for brand: $currentBrand");
    }

    /**
     * Recursively store settings in DB with enhanced messages
     */
    protected function storeSettings(array $settings, string $groupPrefix)
    {
        foreach ($settings as $key => $value) {

            if (is_array($value) && $this->isAssoc($value) === false) {
                // Numeric array => list of setting items
                foreach ($value as $item) {
                    $group = $groupPrefix ?: 'general';

                    $setting = Setting::updateOrCreate(
                        ['key' => $item['key'], 'group' => $group],
                        [
                            'value' => in_array($item['type'], ['imageUploader', 'file'])
                                ? asset(@$item['value'])
                                : @$item['value'],
                            'type' => $item['type'] ?? 'text',
                            'is_env' => $item['is_env'] ?? false,
                            'placeholder' => $item['placeholder'] ?? null,
                            'label' => $item['label'] ?? null,
                            'is_multi_lang' => $item['is_multi_lang'] ?? false
                        ]
                    );

                    if ($setting->wasRecentlyCreated) {
                        $this->command->info("➕ Created: [$group] {$item['key']}");
                    } else {
                        $this->command->comment("🔄 Updated: [$group] {$item['key']}");
                    }
                }
            } elseif (is_array($value)) {
                // Recursive for nested groups
                $newGroupPrefix = $groupPrefix ? $groupPrefix . '.' . $key : $key;
                $this->command->line("📂 Processing group: $newGroupPrefix");
                $this->storeSettings($value, $newGroupPrefix);
            }
        }
    }

    /**
     * Check if array is associative
     */
    protected function isAssoc(array $arr): bool
    {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
