<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeListenerCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:listener
                            {name : The name of the listener}
                            {--module= : The module where the listener will be created}
                            {--e|event= : The event class being listened for}
                            {--queued : Indicates the event listener should be queued}
                            {--f|force : Overwrite the listener if it already exists}
                            {--test : Generate a test for the listener being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new listener class in a module';

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

        $factoryPath = $this->modulePath("src/Listeners/{$name}.php");
        $this->ensureDirectory(dirname($factoryPath));

        $stubName = 'listener';
        if ($this->option('event')) {
            $stubName .= '.typed';
        }

        if ($this->option('queued')) {
            $stubName .= '.queued';
        }

        $stubPath = $this->stubPath('$stubName.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $variables = [
            '{{ namespace }}' => $this->moduleNamespace("Listeners", $name),
            '{{ class }}' => basename($name),
        ];

        if ($namespacedEvent = $this->option('event')) {
            $variables['{{ eventNamespace }}'] = $namespacedEvent;
            $variables['{{ event }}'] = basename(
                str_replace(
                    search: '\\',
                    replace: '/',
                    subject: $namespacedEvent
                )
            );
        }

        $content = $this->replaceStubPlaceholders($stub, $variables);
        $this->writeFile($factoryPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Listeners/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
