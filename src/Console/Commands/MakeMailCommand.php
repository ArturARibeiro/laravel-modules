<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeMailCommand extends AbstractMakeCommand
{
    protected $signature = 'make:module:mail
                            {name : The name of the mailable}
                            {--module= : The module where the mailable will be created}
                            {--markdown= : Create a new Markdown template for the mailable}
                            {--view= : Create a new Blade template for the mailable}
                            {--force : Overwrite the file if it already exists}
                            {--test : Generate a test class for the mailable}
                            {--pest : Generate a Pest test class}
                            {--phpunit : Generate a PHPUnit test class}
                            {--silent : Do not output any message}';

    protected $description = 'Create a new mailable class in a module';

    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $force = $this->getForce();
        $silent = $this->option('silent');
        $markdown = $this->option('markdown');
        $view = $this->option('view');

        if (!$module || !$name) {
            if (!$silent) {
                $this->error('Module and mail class name are required.');
            }
            return self::FAILURE;
        }

        // Determine stub path
        $stubPath = match (true) {
            $markdown => $this->stubPath('markdown-mail.stub'),
            $view     => $this->stubPath('view-mail.stub'),
            default   => $this->stubPath('mail.stub'),
        };

        if (!File::exists($stubPath)) {
            if (!$silent) {
                $this->error("Stub not found: {$stubPath}");
            }
            return self::FAILURE;
        }

        // Determine destination
        $mailPath = $this->modulePath("src/Mail/{$name}.php");
        $this->ensureDirectory(dirname($mailPath));

        $class = basename($name, '.php');
        $namespace = $this->moduleNamespace('Mail', $name);
        $viewName = $markdown ?: $view ?: 'view.name';

        $variables = [
            '{{ namespace }}' => $namespace,
            '{{ class }}' => $class,
            '{{ subject }}' => Str::headline($class),
            '{{ view }}' => $viewName,
        ];

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, $variables);

        if (!$force && File::exists($mailPath)) {
            if (!$silent) {
                $this->warn("Mailable already exists: {$mailPath}");
            }
            return self::FAILURE;
        }

        $this->writeFile($mailPath, $content);

        if (!$silent) {
            $this->info("Mailable created: {$mailPath}");
        }

        // Generate test class if requested
        if ($this->option('test') || $this->option('pest') || $this->option('phpunit')) {
            $testType = $this->option('pest') ? 'pest' : ($this->option('phpunit') ? 'phpunit' : null);
            $this->call('make:module:test', [
                'name' => "Mail/{$class}Test",
                '--module' => $module,
                '--type' => $testType,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
