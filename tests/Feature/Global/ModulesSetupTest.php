<?php

namespace Tests\Feature\Global;

use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class ModulesSetupTest extends TestCase
{
    public function test_the_modules_path_matches_the_directory_on_disk(): void
    {
        $configured = config('modules.paths.modules');

        $this->assertSame(base_path('Modules'), $configured);
        $this->assertDirectoryExists($configured);

        // `Modules` vs `modules` only differs on a case-sensitive filesystem,
        // which is where deployments run.
        $this->assertContains('Modules', scandir(base_path()));
    }

    public function test_composer_autoloads_the_modules_namespace_from_that_same_path(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $this->assertSame('Modules/', $composer['autoload']['psr-4']['Modules\\']);
        $this->assertSame(['Modules/*/composer.json'], $composer['extra']['merge-plugin']['include']);
    }

    public function test_the_module_manager_scans_without_error(): void
    {
        $this->assertIsArray(Module::all());
    }

    public function test_generated_module_paths_follow_the_application_layout(): void
    {
        $generator = config('modules.paths.generator');

        // A module mirrors the root app: Enum, Trait, Scopes, Http/Resources.
        $this->assertSame('app/Enum', $generator['enums']['path']);
        $this->assertSame('app/Trait', $generator['traits']['path']);
        $this->assertSame('app/Scopes', $generator['scopes']['path']);
        $this->assertSame('app/Http/Resources', $generator['resource']['path']);
    }

    public function test_no_frontend_scaffolding_is_generated_for_this_api_only_backend(): void
    {
        $generator = config('modules.paths.generator');

        $this->assertFalse($generator['assets']['generate']);
        $this->assertFalse($generator['views']['generate']);

        $files = config('modules.stubs.files');

        foreach (['views/index', 'views/master', 'assets/js/app', 'assets/sass/app', 'vite', 'package'] as $stub) {
            $this->assertArrayNotHasKey($stub, $files);
        }
    }

    public function test_module_translations_are_auto_registered(): void
    {
        $this->assertTrue(config('modules.paths.generator.lang.generate'));
        $this->assertTrue(config('modules.auto-discover.translations'));
    }
}
