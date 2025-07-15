<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeScopeCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:scope
                            {name : The name of the scope class}
                            {--module= : The module where the scope class will be created}
                            {--f|force : Overwrite the scope class if it already exists}
                            {--test : Generate an Test for the Scope class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new scope class in a module';

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
            $this->error('Module and scope name are required.');
            return self::FAILURE;
        }

        $scopePath = $this->modulePath("src/Scopes/{$name}.php");
        $this->ensureDirectory(dirname($scopePath));

        $stubPath = base_path('stubs/scope.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Scopes', $name),
            '{{ class }}' => basename($name),
        ]);

        if (!$force && File::exists($scopePath)) {
            $this->error("Rule class already exists: {$scopePath}");
            return self::FAILURE;
        }

        $this->writeFile($scopePath, $content);
        $this->info("Rule class created: {$scopePath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Scopes/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
