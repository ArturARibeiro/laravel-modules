<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeTestCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:test
                            {name : The name of the command}
                            {--module= : The module where the command will be created}
                            {--f|force : Overwrite the command if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test class in a module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();

        if (!$module || !$name) {
            return self::FAILURE;
        }

        $testPath = $this->modulePath("tests/{$name}.php");
        $this->ensureDirectory(dirname($testPath));

        $stubPath = base_path('stubs/test.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Tests", $name),
            '{{ class }}' => basename($name),
        ]);

        $this->writeFile($testPath, $content);

        return self::SUCCESS;
    }
}
