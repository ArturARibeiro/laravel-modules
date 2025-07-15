<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeEventCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:event
                            {name : The name of the event}
                            {--module= : The module where the event will be created}
                            {--f|force : Overwrite the event if it already exists}
                            {--test : Generate a test for the event being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event class in a module';

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

        $commandPath = $this->modulePath("src/Events/{$name}.php");
        $this->ensureDirectory(dirname($commandPath));

        $stubPath = base_path('stubs/event.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Events", $name),
            '{{ class }}' => basename($name),
        ]);

        $this->writeFile($commandPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Events/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
