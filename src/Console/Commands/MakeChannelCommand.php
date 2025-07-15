<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Support\Facades\File;

class MakeChannelCommand extends AbstractMakeCommand
{
    protected $signature = 'make:module:channel
                            {name : The name of the channel}
                            {--module= : The module where the channel will be created}
                            {--force : Overwrite the channel if it already exists}';

    protected $description = 'Create a new broadcast channel class in a module';

    public function handle(): int
    {
        $module = $this->getModule();
        $name = $this->getResourceName();
        $force = $this->getForce();

        if (!$module || !$name) {
            $this->error('Module and channel name are required.');
            return self::FAILURE;
        }

        $channelPath = $this->modulePath("src/Broadcasting/{$name}.php");
        $this->ensureDirectory(dirname($channelPath));

        $stubPath = base_path('stubs/channel.stub');

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return self::FAILURE;
        }

        $stub = File::get($stubPath);

        $variables = [
            '{{ namespace }}' => $this->moduleNamespace('Broadcasting', $name),
            '{{ class }}' => basename($name, '.php'),
        ];

        $content = $this->replaceStubPlaceholders($stub, $variables);

        if (!$force && File::exists($channelPath)) {
            $this->error("Channel already exists: {$channelPath}");
            return self::FAILURE;
        }

        $this->writeFile($channelPath, $content);
        $this->info("Channel created: {$channelPath}");

        return self::SUCCESS;
    }
}
