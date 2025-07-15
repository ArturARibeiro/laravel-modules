<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePolicyCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:policy
                            {name : The name of the policy class}
                            {--module= : The module where the policy class will be created}
                            {--model= : The model that the policy applies to}
                            {--guard= : The guard that the policy relies on}
                            {--force : Overwrite the policy class if it already exists}
                            {--test : Generate an accompanying Test for the Policy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class in a module';

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
        $namespacedModel = $this->option('model');

        if (!$module || !$name) {
            $this->error('Module and policy name are required.');
            return self::FAILURE;
        }

        $policyPath = $this->modulePath("src/Policies/{$name}.php");
        $this->ensureDirectory(dirname($policyPath));

        $stubPath = base_path('stubs/policy.plain.stub');
        if ($namespacedModel) {
            $stubPath = base_path('stubs/policy.stub');
        }

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $variables = [
            '{{ namespace }}' => $this->moduleNamespace('Policies', $name),
            '{{ namespacedUserModel }}' => env('AUTH_MODEL', \User\Models\User::class),
            '{{ class }}' => basename($name),
        ];

        if ($namespacedModel) {
            $variables['{{ namespacedModel }}'] = $namespacedModel;
            $variables['{{ model }}'] = basename($namespacedModel);
            $variables['{{ modelVariable }}'] = Str::camel(basename($namespacedModel));
            $variables['{{ user }}'] = Str::camel(
                value: basename(env('AUTH_MODEL', \User\Models\User::class))
            );
        }

        $content = $this->replaceStubPlaceholders($stub, $variables);

        if (!$force && File::exists($policyPath)) {
            $this->error("Policy class already exists: {$policyPath}");
            return self::FAILURE;
        }

        $this->writeFile($policyPath, $content);
        $this->info("Policy class created: {$policyPath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Policies/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
