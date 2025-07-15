<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeMiddlewareCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:middleware
                            {name : The name of the middleware}
                            {--module= : The module where the middleware will be created}
                            {--f|force : Overwrite the middleware if it already exists}
                            {--test : Generate a test for the middleware being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new middleware class in a module';

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

        $factoryPath = $this->modulePath("src/Http/Middlewares/{$name}.php");
        $this->ensureDirectory(dirname($factoryPath));

        $stubPath = base_path("stubs/middleware.stub");
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Http\\Middlewares", $name),
            '{{ class }}' => basename($name),
        ]);

        $this->writeFile($factoryPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Http/Middlewares/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
