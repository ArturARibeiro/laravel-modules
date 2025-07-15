<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeNotificationCommand extends AbstractMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module:notification
                            {name : The name of the notification class}
                            {--module= : The module where the notification class will be created}
                            {--f|force : Overwrite the notification class if it already exists}
                            {--test : Generate an Test for the Notification class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new notification class in a module';

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
            $this->error('Module and notification name are required.');
            return self::FAILURE;
        }

        $mailPath = $this->modulePath("src/Notifications/{$name}.php");
        $this->ensureDirectory(dirname($mailPath));

        $stubPath = $this->stubPath('notification.stub');
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ namespace }}' => $this->moduleNamespace('Notifications', $name),
            '{{ class }}' => basename($name),
        ]);

        if (!$force && File::exists($mailPath)) {
            $this->error("Mail class already exists: {$mailPath}");
            return self::FAILURE;
        }

        $this->writeFile($mailPath, $content);
        $this->info("Mail class created: {$mailPath}");

        if ($this->option('test')) {
            $this->call('make:module:test', [
                'name' => "Notifications/{$name}Test",
                '--module' => $module,
                '--force' => $force,
            ]);
        }

        return self::SUCCESS;
    }
}
