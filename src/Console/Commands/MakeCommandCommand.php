<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCommandCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:command
                            {name : The name of the command}
                            {--module= : The module where the command will be created}
                            {--f|force : Overwrite the command if it already exists}
                            {--test : Generate a test for the command being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new command in a module';

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

        $commandPath = $this->modulePath("src/Console/Commands/{$name}.php");
        $this->ensureDirectory(dirname($commandPath));

        $stubPath = base_path('stubs/console.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Console\\Commands", $name),
            '{{ class }}' => basename($name),
            '{{ command }}' => strtolower(
                str_replace('\\', ':', Str::snake($name))
            ),
        ]);

        $this->writeFile($commandPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Console/Commands/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
