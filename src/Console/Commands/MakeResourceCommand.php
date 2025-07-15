<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeResourceCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:resource
                            {name : The name of the resource class}
                            {--module= : The module where the resource class will be created}
                            {--mixin= : The class to be used with @mixin directive}
                            {--c|collection : Create a resource collection}
                            {--f|force : Overwrite the resource class if it already exists}
                            {--test : Generate a test for the resource being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource class in a module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $mixin = $this->option('mixin');
        $force = $this->getForce();

        if (!$module || !$name) {
            $this->error('Module and resource name are required.');
            return self::FAILURE;
        }

        $resourcePath = $this->modulePath("src/Http/Resources/{$name}.php");
        $this->ensureDirectory(dirname($resourcePath));

        if ($this->option('collection') && !Str::endsWith($name, 'Collection')) {
            $name .= 'Collection';
        }

        $stubPath = $this->stubPath('resource.stub');
        if (Str::endsWith($name, 'Collection')) {
            $stubPath = $this->stubPath('resource-collection.stub');
        }

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Http\Resources', $name),
            '{{ class }}' => basename($name),
            '{{ mixin }}' => $mixin
        ]);

        if (!$force && File::exists($resourcePath)) {
            $this->error("Resource class already exists: {$resourcePath}");
            return self::FAILURE;
        }

        $this->writeFile($resourcePath, $content);
        $this->info("Resource class created: {$resourcePath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Http/Resources/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
