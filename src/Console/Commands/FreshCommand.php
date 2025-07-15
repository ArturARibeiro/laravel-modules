<?php

namespace Aar\LaravelModules\Console\Commands;

use Illuminate\Console\Command;

class FreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:fresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the module autoload and providers configuration.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Refreshing autoload and providers configuration.');
        $this->updateComposerAutoload();
        $this->updateProvidersConfig();
        $this->info("Autoload and provider configurations updated. Run 'composer dump-autoload' to apply changes.");
    }

    /**
     * Update the autoload section of the composer.json file.
     *
     * @return void
     */
    private function updateComposerAutoload(): void
    {
        $composerFile = base_path('composer.json');
        $composerData = json_decode(file_get_contents($composerFile), true);

        $modulesDir = base_path('modules');
        $modules = glob($modulesDir . '/*', GLOB_ONLYDIR);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            $composerData['autoload']['psr-4']["$moduleName\\"] = "modules/$moduleName/src/";

            if (is_dir("$modulePath/database/seeders")) {
                $composerData['autoload']['psr-4']["$moduleName\\Database\\Seeders\\"] = "modules/$moduleName/database/seeders/";
            }

            if (is_dir("$modulePath/database/factories")) {
                $composerData['autoload']['psr-4']["$moduleName\\Database\\Factories\\"] = "modules/$moduleName/database/factories/";
            }

            if (is_dir("$modulePath/tests")) {
                $composerData['autoload-dev']['psr-4']["$moduleName\\Tests\\"] = "modules/$moduleName/tests/";
            }
        }

        file_put_contents(
            $composerFile,
            json_encode($composerData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $this->info("composer.json autoload updated. Run 'composer dump-autoload' to apply changes.");
    }

    /**
     * Update the providers configuration in bootstrap/providers.php.
     *
     * @return void
     */
    private function updateProvidersConfig(): void
    {
        $providersFile = base_path('bootstrap/providers.php');

        if (!file_exists($providersFile)) {
            $this->error("The providers.php configuration file does not exist.");
            return;
        }

        $modulesDir = base_path('modules');
        $modules = glob($modulesDir . '/*', GLOB_ONLYDIR);

        $providers = require $providersFile;
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $providerClass = "$moduleName\\Providers\\{$moduleName}ServiceProvider";

            if (!in_array($providerClass, $providers)) {
                $providers[] = $providerClass;
            }
        }

        $providersExport = $this->formatArrayExport($providers);
        $providersContent = <<<PHP
<?php

return {$providersExport};
PHP;

        file_put_contents($providersFile, $providersContent);
        $this->info('Providers configuration updated successfully.');
    }

    /**
     * Format the array export to match PHP array syntax.
     *
     * @param array $array
     * @return string
     */
    private function formatArrayExport(array $array): string
    {
        $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return str_replace(['{', '}', ':'], ['[', ']', ' =>'], $json);
    }

}
