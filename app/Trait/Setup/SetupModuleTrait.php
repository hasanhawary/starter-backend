<?php

namespace App\Trait\Setup;

use Illuminate\Support\Facades\Artisan;

trait SetupModuleTrait
{
    /**
     * @param string $module
     * @return void
     */
    public function setupModule(string $module): void
    {
        $action = $this->choice("Choose action for the $module module", ['disable', 'active', 'refresh']);

        match ($action) {
            'active' => $this->enableModule($module),
            'disable' => $this->disableModule($module),
            'refresh' => $this->refreshModule($module),
            default => $this->error('Invalid action.')
        };
    }

    /**
     * @param $module
     * @return void
     */
    private function enableModule($module): void
    {
        Artisan::call("module:enable $module");
        Artisan::call("module:migrate $module");
        Artisan::call("module:seed $module");

        $moduleUpper = strtoupper($module);
        updateDotEnv(["MODULE_{$moduleUpper}_ENABLE" => true]);

        $this->info("Module $module is enabled successfully.");
    }

    /**
     * @param $module
     * @return void
     */
    private function disableModule($module): void
    {
        Artisan::call("module:migrate-reset $module");
        Artisan::call("module:disable $module");

        $moduleUpper = strtoupper($module);
        updateDotEnv(["MODULE_{$moduleUpper}_ENABLE" => false]);

        $this->info("Module $module is disabled successfully.");
    }

    /**
     * @param $module
     * @return void
     */
    private function refreshModule($module): void
    {
        Artisan::call("module:migrate-fresh $module");
        Artisan::call("module:seed $module");

        $this->info("Module $module is refresh successfully.");
    }
}
