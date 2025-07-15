<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeControllerCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:controller
                            {name : The name of the controller}
                            {--module= : The module where the controller will be created}
                            {--api : Exclude the create and edit methods from the controller}
                            {--model= : Generate a resource controller for the given model}
                            {--R|requests= : Generate FormRequest classes for store and update}
                            {--f|force : Overwrite the controller if it already exists}
                            {--test : Generate a test for the controller being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller class in a module';

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

        if (!$module || !$name) {
            return self::FAILURE;
        }

        $controllerPath = $this->modulePath("src/Http/Controllers/{$name}.php");
        $this->ensureDirectory(dirname($controllerPath));

        $stubName = 'controller';
        if ($this->option('model')) {
            $stubName .= '.model';
        }

        if ($this->option('api')) {
            $stubName .= '.api';
        }

        $stubPath = base_path("stubs/{$stubName}.stub");
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $variables = [
            '{{ namespace }}' => $this->moduleNamespace("Http\\Controllers"),
            '{{ rootNamespace }}' => $this->moduleNamespace(),
            '{{ namespacedRequests }}' => 'Illuminate\Http\Request;',
            '{{ storeRequest }}' => 'Request',
            '{{ updateRequest }}' => 'Request',
            '{{ class }}' => basename($name),
        ];

        if ($namespacedModel = $this->option('model')) {
            $variables['{{ namespacedModel }}'] = $namespacedModel;
            $variables['{{ model }}'] = class_basename($namespacedModel);
            $variables['{{ modelVariable }}'] = Str::camel(
                class_basename($namespacedModel)
            );
        }

        $content = $this->replaceStubPlaceholders($stub, $variables);
        $this->writeFile($controllerPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Http/Controllers/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
