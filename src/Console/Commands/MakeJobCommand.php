<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeJobCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:job
                            {name : The name of the job}
                            {--module= : The module where the job will be created}
                            {--sync : Indicates that job should be synchronous}
                            {--f|force : Overwrite the job if it already exists}
                            {--test : Generate a test for the job being created.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new job class in a module';

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

        $factoryPath = $this->modulePath("src/Jobs/{$name}.php");
        $this->ensureDirectory(dirname($factoryPath));

        $stubPath = $this->stubPath('job.queued.stub');
        if ($this->option('sync')) {
            $stubPath = $this->stubPath('job.stub');
        }

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace("Jobs", $name),
            '{{ class }}' => basename($name),
        ]);

        $this->writeFile($factoryPath, $content);

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Jobs/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
