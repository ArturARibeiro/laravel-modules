<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeObserverCommand extends AbstractMakeCommand
{
    protected $signature = 'make:module:observer
                            {name : The name of the observer}
                            {--module= : The module where the observer will be created}
                            {--model= : The model that the observer applies to}
                            {--force : Overwrite the observer if it already exists}
                            {--silent : Do not output any message}';

    protected $description = 'Create a new model observer class in a module';

    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $force = $this->getForce();
        $silent = $this->option('silent');
        $model = $this->option('model');

        if (!$module || !$name) {
            if (!$silent) {
                $this->error('Module and observer name are required.');
            }
            return self::FAILURE;
        }

        $observerPath = $this->modulePath("src/Observers/{$name}.php");
        $this->ensureDirectory(dirname($observerPath));

        $stubPath = $model
            ? base_path('stubs/observer.stub')
            : base_path('stubs/observer.plain.stub');

        if (!File::exists($stubPath)) {
            if (!$silent) {
                $this->error("Stub file not found: {$stubPath}");
            }
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $class = basename($name, '.php');

        $variables = [
            '{{ namespace }}' => $this->moduleNamespace('Observers', $name),
            '{{ class }}' => $class,
        ];

        if ($model) {
            $modelClass = Str::start($model, '\\'); // Ensure it's fully qualified
            $modelBase = class_basename($model);
            $modelVariable = Str::camel($modelBase);

            $variables['{{ namespacedModel }}'] = trim($modelClass, '\\');
            $variables['{{ model }}'] = $modelBase;
            $variables['{{ modelVariable }}'] = $modelVariable;
        }

        $content = $this->replaceStubPlaceholders($stub, $variables);

        if (!$force && File::exists($observerPath)) {
            if (!$silent) {
                $this->error("Observer already exists: {$observerPath}");
            }
            return self::FAILURE;
        }

        $this->writeFile($observerPath, $content);

        if (!$silent) {
            $this->info("Observer created: {$observerPath}");
        }

        return self::SUCCESS;
    }
}
