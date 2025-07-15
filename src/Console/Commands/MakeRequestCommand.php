<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRequestCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:request
                            {name : The name of the request class}
                            {--module= : The module where the request class will be created}
                            {--f|force : Overwrite the request class if it already exists}
                            {--test : Generate a test for the request being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new request class in a module';

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
            $this->error('Module and resource name are required.');
            return self::FAILURE;
        }

        $requestPath = $this->modulePath("src/Http/Request/{$name}.php");
        $this->ensureDirectory(dirname($requestPath));

        $stubPath = base_path('stubs/request.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Http\Request', $name),
            '{{ class }}' => basename($name),
        ]);

        if (!$force && File::exists($requestPath)) {
            $this->error("Request class already exists: {$requestPath}");
            return self::FAILURE;
        }

        $this->writeFile($requestPath, $content);
        $this->info("Request class created: {$requestPath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Http/Request/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
