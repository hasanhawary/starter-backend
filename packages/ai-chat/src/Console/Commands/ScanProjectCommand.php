<?php

namespace AiChat\Console\Commands;

use AiChat\Generator\ManifestGenerator;
use AiChat\Generator\ToolGenerator;
use AiChat\Scanner\ProjectScanner;
use Illuminate\Console\Command;

class ScanProjectCommand extends Command
{
    protected $signature = 'ai-chat:scan {--generate : Generate MCP tools} {--force : Overwrite existing tools}';

    protected $description = 'Scan the Laravel project and generate AI tools';

    public function handle(): int
    {
        $this->components->info('Scanning project...');

        $scanner = app(ProjectScanner::class);
        $projectMap = $scanner->scan();

        $this->displayScanResults($projectMap);

        if ($this->option('generate')) {
            $this->generateTools($projectMap);
            $this->generateManifest($projectMap);
        }

        return self::SUCCESS;
    }

    protected function displayScanResults(array $projectMap): void
    {
        $this->newLine();
        $this->components->info('Scan Results:');
        $this->newLine();

        $modelCount = count($projectMap['models'] ?? []);
        $routeCount = count($projectMap['routes']['routes'] ?? []);
        $controllerCount = count($projectMap['controllers'] ?? []);
        $serviceCount = count($projectMap['services'] ?? []);
        $policyCount = count($projectMap['policies'] ?? []);
        $migrationCount = count($projectMap['migrations'] ?? []);

        $this->components->twoColumnDetail('Models', (string) $modelCount);
        $this->components->twoColumnDetail('Routes', (string) $routeCount);
        $this->components->twoColumnDetail('Controllers', (string) $controllerCount);
        $this->components->twoColumnDetail('Services', (string) $serviceCount);
        $this->components->twoColumnDetail('Policies', (string) $policyCount);
        $this->components->twoColumnDetail('Migrations', (string) $migrationCount);

        if ($modelCount > 0) {
            $this->newLine();
            $this->components->info('Discovered Models:');

            foreach (array_keys($projectMap['models'] ?? []) as $modelClass) {
                $this->line("  - {$modelClass}");
            }
        }

        $this->newLine();

        if (! $this->option('generate')) {
            $this->components->info('Run with --generate to create MCP tools from scan results.');
        }
    }

    protected function generateTools(array $projectMap): void
    {
        $this->newLine();
        $this->components->info('Generating MCP tools...');

        $generator = app(ToolGenerator::class);
        $generated = $generator->generate($projectMap);

        if (empty($generated)) {
            $this->components->warn('No tools were generated.');

            return;
        }

        foreach (array_filter($generated) as $path) {
            $relativePath = str_replace(base_path().'/', '', $path);
            $this->components->task("Generated {$relativePath}", fn () => true);
        }

        $this->newLine();
        $this->components->info(count(array_filter($generated)).' tool(s) generated successfully.');
    }

    protected function generateManifest(array $projectMap): void
    {
        $this->newLine();
        $this->components->info('Generating project manifest...');

        $manifestGenerator = app(ManifestGenerator::class);
        $manifestGenerator->publish($projectMap);

        $this->components->task('Published config/ai-project.php', fn () => true);
    }
}
