<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeSeederCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:seeder
                            {name : The name of the seeder}
                            {--module= : The module where the seeder will be created}
                            {--f|force : Overwrite the seeder if it already exists}
                            {--test : Generate a test for the seeder being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seeder class in a module';

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

        $factoryPath = $this->modulePath("database/seeders/{$name}.php");
        $this->ensureDirectory(dirname($factoryPath));

        $stubPath = base_path('stubs/seeder.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Database\\Seeders", $name),
            '{{ class }}' => basename($name),
        ]);
        $this->writeFile($factoryPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Database/Seeders/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
