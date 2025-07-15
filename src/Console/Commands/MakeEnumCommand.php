<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeEnumCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:enum
                            {name : The name of the command}
                            {--module= : The module where the command will be created}
                            {--s|string : Generate a string backed enum.}
                            {--i|int : Generate an integer backed enum.}
                            {--f|force : Overwrite the command if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new enum in a module';

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

        $commandPath = $this->modulePath("src/Enums/{$name}.php");
        $this->ensureDirectory(dirname($commandPath));

        $type = '';
        $stubPath = base_path('stubs/enum.stub');
        if ($this->option('string') || $this->option('int')) {
            $stubPath = base_path('stubs/enum.backed.stub');
            $type = $this->option('string') ? 'string' : 'int';
        }

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Enums", $name),
            '{{ class }}' => basename($name),
            '{{ type }}' => $type,
        ]);

        $this->writeFile($commandPath, $content);

        return self::SUCCESS;
    }
}
