<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFactoryCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:factory
                            {name : The name of the factory}
                            {--module= : The module where the factory will be created}
                            {--model= : Generate a factory for the given model}
                            {--f|force : Overwrite the factory if it already exists}
                            {--test : Generate a test for the factory being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new factory class in a module';

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

        $factory = basename($name);

        // Remove o sufixo "Factory" se já estiver presente
        if (Str::endsWith($factory, 'Factory')) {
            $factory = Str::beforeLast($factory, 'Factory');
        }

        // Adiciona o sufixo "Factory" caso não esteja presente
        if (!Str::endsWith($name, 'Factory')) {
            $name .= 'Factory';
        }

        $factoryPath = $this->modulePath("database/factories/{$name}.php");
        $this->ensureDirectory(dirname($factoryPath));

        $stubPath = $this->stubPath('factory.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $variables = [
            '{{ factoryNamespace }}' => $this->moduleNamespace("Database\\Factories", $name),
            '{{ factory }}' => $factory,
            '{{ namespacedModel }}' => '',
        ];

        if ($namespacedModel = $this->option('model')) {
            $variables['{{ namespacedModel }}'] = $namespacedModel;
        }

        $content = $this->replaceStubPlaceholders($stub, $variables);
        $this->writeFile($factoryPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Database/Factories/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
