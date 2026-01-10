<?php

namespace Database\Seeders\Central;

use App\Models\Central\Setting;
use App\Services\Global\SettingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Arabic;

class SettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentBrand = config('brands.default_brand');
        $this->command->info("🔹 Seeder started for brand: $currentBrand");

        $brandsConfig = config('brands.brands');

        if (!isset($brandsConfig[$currentBrand])) {
            $this->command->warn("⚠️ Brand '$currentBrand' not found in config. Seeder skipped.");
            return;
        }

        $brandSettings = $brandsConfig[$currentBrand];

        DB::table('settings')->truncate();

        $this->storeSettings($brandSettings);

        app(SettingService::class)->clearCache();

        $this->command->info("✅ Seeder finished for brand: $currentBrand");
    }

    /**
     * Recursively store settings
     *
     * @param array $settings
     * @param string|null $groupPrefix
     */
    protected function storeSettings(array $settings, ?string $groupPrefix = null): void
    {
        foreach ($settings as $key => $value) {

            if (is_array($value)) {
                // Check if this is a numeric array of settings items
                if (!$this->isAssoc($value)) {
                    foreach ($value as $item) {
                        $group = $groupPrefix ? $groupPrefix . '.' . $key : $key;

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
                } else {
                    // Associative array = nested group
                    $newGroupPrefix = $groupPrefix ? $groupPrefix . '.' . $key : $key;
                    $this->command->line("📂 Processing group: $newGroupPrefix");
                    $this->storeSettings($value, $newGroupPrefix);
                }
            }
        }
    }

    /**
     * Check if array is associative
     *
     * @param array $arr
     * @return bool
     */
    protected function isAssoc(array $arr): bool
    {
        if ([] === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
