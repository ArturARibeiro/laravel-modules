<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModelCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:model
                            {name : The name of the model}
                            {--module= : The module where the model will be created}
                            {--a|all : Generate a migration, seeder, factory, policy, resource controller, and form request classes for the model}
                            {--m|migration : Create a new migration file for the model}
                            {--c|controller : Create a new controller for the model}
                            {--f|factory : Create a new factory for the model}
                            {--s|seed : Create a new seeder for the model}
                            {--force : Overwrite the model if it already exists}
                            {--pivot : Indicates if the generated model should be a custom intermediate table model}
                            {--morph-pivot : Indicates if the generated model should be a custom polymorphic intermediate table model}
                            {--p|policy : Create a new policy for the model}
                            {--api : Indicates if the generated controller should be an API resource controller}
                            {--R|requests : Create new form request classes and use them in the resource controller}
                            {--test : Generate an accompanying Test for the Model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model class in a module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $force = $this->getForce();
        $class = basename($name, '.php');
        $namespace = $this->moduleNamespace('Models', $name);

        if (!$module || !$name) {
            $this->error('Module and model name are required.');
            return self::FAILURE;
        }

        $modelPath = $this->modulePath("src/Models/{$name}.php");
        $this->ensureDirectory(dirname($modelPath));

        $stubPath = base_path('stubs/model.stub');

        if ($this->option('pivot')) {
            $stubPath = base_path('stubs/model.pivot.stub');
        }

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $variables = [
            '{{ namespace }}' => $this->moduleNamespace('Models', $name),
            '{{ class }}' => basename($name, '.php'),
            '{{ factoryImport }}' => '',
            '{{ factory }}' => '//',
        ];

        if ($this->option('factory')) {
            $variables['{{ factoryImport }}'] = "use Illuminate\Database\Eloquent\Factories\HasFactory;";
            $variables['{{ factory }}'] = "use HasFactory;";
        }

        $content = $this->replaceStubPlaceholders($stub, $variables);

        if (!$force && File::exists($modelPath)) {
            $this->error("Model already exists: {$modelPath}");
            return self::FAILURE;
        }

        $this->writeFile($modelPath, $content);
        $this->info("Model created: {$modelPath}");

        if ($this->option('all') || $this->option('migration')) {
            $tableName = Str::snake(Str::pluralStudly($name));

            $this->call('make:module:migration', [
                'name' => "create_{$tableName}_table",
                '--module' => $module,
                '--create' => $tableName,
            ]);
        }

        if ($this->option('all') || $this->option('factory')) {
            $this->call('make:module:factory', [
                'name' => $name,
                '--module' => $module,
                '--model' => "{$namespace}\\{$class}",
                '--force' => $force,
                '--test' => $this->option('test'),
            ]);
        }

        if ($this->option('all') || $this->option('seed')) {
            $this->call('make:module:seeder', [
                'name' => "{$name}Seeder",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        if ($this->option('all') || $this->option('policy')) {
            $this->call('make:module:policy', [
                'name' => "{$name}Policy",
                '--module' => $module,
                '--model' => "{$namespace}\\{$class}",
                '--force' => $force,
            ]);
        }

        if ($this->option('all') || $this->option('controller')) {
            $this->call('make:module:controller', [
                'name' => "{$name}Controller",
                '--module' => $module,
                '--model' => "{$namespace}\\{$class}",
                '--api' => $this->option('api'),
                '--requests' => $this->option('requests'),
                '--force' => $force,
                '--test' => $this->option('test'),
            ]);
        }

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Models/{$name}Test",
                '--module' => $module,
                '--force' => $force,
                '--test' => $this->option('test'),
            ]);
        }

        return self::SUCCESS;
    }
}
