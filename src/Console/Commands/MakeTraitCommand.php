<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeTraitCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:trait
                            {name : The name of the trait class}
                            {--module= : The module where the trait class will be created}
                            {--f|force : Overwrite the trait class if it already exists}
                            {--test : Generate an Test for the Trait class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new trait class in a module';

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
            $this->error('Module and trait name are required.');
            return self::FAILURE;
        }

        $traitPath = $this->modulePath("src/Traits/{$name}.php");
        $this->ensureDirectory(dirname($traitPath));

        $stubPath = base_path('stubs/trait.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Traits', $name),
            '{{ class }}' => basename($name),
        ]);

        if (!$force && File::exists($traitPath)) {
            $this->error("Rule class already exists: {$traitPath}");
            return self::FAILURE;
        }

        $this->writeFile($traitPath, $content);
        $this->info("Rule class created: {$traitPath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Traits/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
