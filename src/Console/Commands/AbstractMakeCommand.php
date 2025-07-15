<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractMakeCommand extends Command
{
    /**
     * The module where the resource should be created.
     *
     * @var string|null
     */
    private ?string $module = null;

    /**
     * The name of the resource class
     *
     * @var string|null
     */
    private ?string $resourceName = null;

    /**
     * Overwrite the resource if it already exists
     *
     * @var bool
     */
    private ?bool $force = null;

    /**
     * Ensure the directory exists, creating it if necessary.
     *
     * @param string $path
     * @return void
     */
    protected function ensureDirectory(string $path): void
    {
        try {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->info("Directory created: {$path}");
            }
        } catch (\Exception $e) {
            $this->error("Failed to create directory: {$path}. Error: " . $e->getMessage());
        }
    }

    /**
     * Replace placeholders in the stub with the provided data.
     *
     * @param string $stub
     * @param array $variables
     * @return string
     */
    protected function replaceStubPlaceholders(string $stub, array $variables): string
    {
        return str_replace(
            search: array_keys($variables),
            replace: array_values($variables),
            subject: $stub
        );
    }

    /**
     * Write a file to the specified path.
     *
     * @param string $path
     * @param string $content
     * @return void
     */
    protected function writeFile(string $path, string $content): void
    {
        try {
            if (File::exists($path) && !$this->getForce()) {
                $this->warn("File '{$path}' already exists. Use --force to overwrite.");
                return;
            }

            File::put($path, $content);
            $this->info("File '{$path}' created successfully.");
        } catch (\Exception $e) {
            $this->error("Failed to write file: {$path}. Error: " . $e->getMessage());
        }
    }

    protected function generateTest(string $path)
    {

    }

    protected function getModule(): ?string
    {
        if ($this->module) {
            return $this->module;
        }

        $modulesPath = config(
            key: 'modules.path',
            default: base_path('modules')
        );

        if (!File::exists($modulesPath)) {
            $this->error("Modules directory not found: {$modulesPath}");
            return null;
        }

        $modules = array_map(
            callback: fn($dir) => basename($dir),
            array: File::directories($modulesPath)
        );

        $module = $this->option('module') ?: $this->choice(
            question: 'Which module do you want to use?',
            choices: $modules
        );

        if (!in_array($module, $modules)) {
            $this->error('Module not found.');
            return null;
        }

        return $this->module = $module;
    }

    protected function getResourceName(): ?string
    {
        if ($this->resourceName) {
            return $this->resourceName;
        }

        return $this->resourceName = $this->argument('name') ?: $this->ask(
            question: 'What is the name of the resource?'
        );
    }

    protected function getForce(): bool
    {
        if ($this->force !== null) {
            return $this->force;
        }

        return $this->force = (bool) $this->option('force');
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class.'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['module', null, InputOption::VALUE_OPTIONAL, 'The module where the resource should be created.'],
            ['force', 'f', InputOption::VALUE_NONE, 'Force overwrite the resource if it already exists.'],
            ['test', null, InputOption::VALUE_NONE, 'Generate a test for the resource being created.'],
        ];
    }

    protected function modulePath(string $path = ''): string
    {
        $modulePath = config(
            key: 'modules.path',
            default: base_path('modules')
        );

        return str_replace(
            search: ['//'],
            replace: ['/'],
            subject: "$modulePath/{$this->getModule()}/$path"
        );
    }

    protected function moduleNamespace(string $namespace = '', ?string $filePath = null): string
    {
        $moduleNamespace = "{$this->getModule()}\\$namespace";

        if ($filePath) {
            $dirname = dirname($filePath);

            if ($dirname && $dirname !== '.') {
                $moduleNamespace .= "\\" . str_replace(['/', '.'], ['\\', ''], $dirname);
            }
        }

        return $moduleNamespace;
    }

    protected function stubPath(string $stub): string
    {
        return __DIR__ . "/../../../stubs/$stub";
    }
}
