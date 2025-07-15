<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeExceptionCommand extends AbstractMakeCommand
{
    protected $signature = 'make:module:exception
                            {name : The name of the exception}
                            {--module= : The module where the exception will be created}
                            {--force : Overwrite the exception if it already exists}
                            {--render : Include an empty render() method}
                            {--report : Include an empty report() method}
                            {--silent : Do not output any message}';

    protected $description = 'Create a new custom exception class in a module';

    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $force = $this->getForce();
        $silent = $this->option('silent');

        if (!$module || !$name) {
            if (!$silent) {
                $this->error('Module and exception name are required.');
            }
            return self::FAILURE;
        }

        $stubPath = $this->resolveStubPath();

        if (!File::exists($stubPath)) {
            if (!$silent) {
                $this->error("Stub file not found: {$stubPath}");
            }
            return self::FAILURE;
        }

        $exceptionPath = $this->modulePath("src/Exceptions/{$name}.php");
        $this->ensureDirectory(dirname($exceptionPath));

        $stub = File::get($stubPath);

        $variables = [
            '{{ namespace }}' => $this->moduleNamespace('Exceptions', $name),
            '{{ class }}' => basename($name, '.php'),
        ];

        $content = $this->replaceStubPlaceholders($stub, $variables);

        if (!$force && File::exists($exceptionPath)) {
            if (!$silent) {
                $this->error("Exception already exists: {$exceptionPath}");
            }
            return self::FAILURE;
        }

        $this->writeFile($exceptionPath, $content);

        if (!$silent) {
            $this->info("Exception created: {$exceptionPath}");
        }

        return self::SUCCESS;
    }

    /**
     * Resolve the appropriate stub file path based on flags.
     */
    protected function resolveStubPath(): string
    {
        $render = $this->option('render');
        $report = $this->option('report');

        return match (true) {
            $render && $report => $this->stubPath('exception-render-report.stub'),
            $render => $this->stubPath('exception-render.stub'),
            $report => $this->stubPath('exception-report.stub'),
            default => $this->stubPath('exception.stub'),
        };
    }
}
