<?php

namespace App\Console\Commands\Setup;

use App\Trait\Setup\SetupModuleTrait;
use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

class SetupModule extends Command
{
    use SetupModuleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-module {--name= : Module Name you want to setup it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Using to control modules behavior in case of status is active';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $moduleName = $this->option('name');

        if (empty($moduleName)) {
            $this->error('Please specify the module name using --name option.');
            return;
        }

        if (!$this->moduleExists($moduleName)) {
            $this->error("Module '$moduleName' does not exist.");
            return;
        }

        $this->setupModule($moduleName);
    }

    /**
     * @param $moduleName
     * @return bool
     */
    protected function moduleExists($moduleName): bool
    {
        return collect(Module::all())->contains(function ($module) use ($moduleName) {
            return $module->getName() === $moduleName;
        });
    }
}
