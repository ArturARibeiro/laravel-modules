<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeProviderCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:provider
                            {name : The name of the provider class}
                            {--module= : The module where the provider class will be created}
                            {--f|force : Overwrite the provider class if it already exists}
                            {--test : Generate an Test for the Provider class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new provider class in a module';

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
            $this->error('Module and provider name are required.');
            return self::FAILURE;
        }

        $providerPath = $this->modulePath("src/Providers/{$name}.php");
        $this->ensureDirectory(dirname($providerPath));

        $stubPath = base_path('stubs/provider.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Providers', $name),
            '{{ class }}' => basename($name),
        ]);

        if (!$force && File::exists($providerPath)) {
            $this->error("Rule class already exists: {$providerPath}");
            return self::FAILURE;
        }

        $this->writeFile($providerPath, $content);
        $this->info("Rule class created: {$providerPath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Providers/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
