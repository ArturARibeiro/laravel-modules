<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeStructureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module
                            {name? : The name of the module}
                            {--f|fresh : Refresh the module autoload and providers configuration.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module with default structure.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = Str::studly(
            value: $this->argument('name') ?: $this->ask(
                question: 'What is the name of the module?'
            )
        );

        $modulePath = config('modules.path') . '/' . $moduleName;

        if (File::isDirectory($modulePath)) {
            $this->error('Module already exists!');
            return self::FAILURE;
        }

        $this->createModuleStructure($modulePath);
        $this->createBaseController($moduleName, $modulePath);
        $this->createBaseTestCase($moduleName, $modulePath);
        $this->createModuleServiceProvider($moduleName, $modulePath);

        if ($this->option('fresh')) {
            $this->call('module:fresh');
        }

        return self::SUCCESS;
    }

    private function createModuleStructure(string $modulePath): void
    {
        $directories = [
            "{$modulePath}/database/factories",
            "{$modulePath}/database/migrations",
            "{$modulePath}/database/seeders",
            "{$modulePath}/src/Http/Controllers",
            "{$modulePath}/src/Providers",
            "{$modulePath}/tests/"
        ];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info('Created directory ' . $directory);
            }
        }
    }

    private function createBaseController(string $moduleName, string $modulePath): void
    {
        $stubPath = $this->stubPath('scope.stub');

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return;
        }

        File::put(
            path: "{$modulePath}/src/Http/Controllers/Controller.php",
            contents: $this->replaceStubPlaceholders(File::get($stubPath), [
                '{{ namespace }}' => "{$moduleName}\\Http\\Controllers",
                '{{ rootNamespace }}' => "App\\Http\\Controllers",
                '{{ class }}' => 'Controller'
            ])
        );
    }

    private function createBaseTestCase(string $moduleName, string $modulePath): void
    {
        $stubPath = $this->stubPath('test-case.stub');

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return;
        }

        File::put(
            path: "{$modulePath}/tests/{$moduleName}TestCase.php",
            contents: $this->replaceStubPlaceholders(File::get($stubPath), [
                '{{ namespace }}' => "{$moduleName}\\Tests",
                '{{ class }}' => "{$moduleName}TestCase"
            ])
        );
    }

    private function createModuleServiceProvider(string $moduleName, string $modulePath): void
    {
        $stubPath = $this->stubPath('provider.stub');

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return;
        }

        File::put(
            path: "{$modulePath}/src/Providers/{$moduleName}ServiceProvider.php",
            contents: $this->replaceStubPlaceholders(File::get($stubPath), [
                '{{ namespace }}' => "{$moduleName}\\Providers",
                '{{ class }}' => "{$moduleName}ServiceProvider"
            ])
        );
    }

    /**
     * Replace placeholders in the stub with the provided data.
     *
     * @param string $stub
     * @param array $variables
     * @return string
     */
    private function replaceStubPlaceholders(string $stub, array $variables): string
    {
        return str_replace(
            search: array_keys($variables),
            replace: array_values($variables),
            subject: $stub
        );
    }
}
