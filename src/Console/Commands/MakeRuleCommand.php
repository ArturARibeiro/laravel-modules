<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeRuleCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:rule
                            {name : The name of the rule class}
                            {--module= : The module where the rule class will be created}
                            {--f|force : Overwrite the rule class if it already exists}
                            {--test : Generate an Test for the Rule class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new rule class in a module';

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
            $this->error('Module and rule name are required.');
            return self::FAILURE;
        }

        $rulePath = $this->modulePath("src/Rules/{$name}.php");
        $this->ensureDirectory(dirname($rulePath));

        $stubPath = $this->stubPath('rule.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Rules', $name),
            '{{ class }}' => basename($name),
        ]);

        if (!$force && File::exists($rulePath)) {
            $this->error("Rule class already exists: {$rulePath}");
            return self::FAILURE;
        }

        $this->writeFile($rulePath, $content);
        $this->info("Rule class created: {$rulePath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Rules/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
