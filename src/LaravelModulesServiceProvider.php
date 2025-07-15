<?php

namespace Aar\LaravelModules;

use Illuminate\Support\ServiceProvider;

class LaravelModulesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Aar\LaravelModules\Console\Commands\FreshCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeChannelCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeCommandCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeControllerCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeEnumCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeEventCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeExceptionCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeFactoryCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeJobCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeListenerCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeMailCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeMiddlewareCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeMigrationCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeModelCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeNotificationCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeObserverCommand::class,
                \Aar\LaravelModules\Console\Commands\MakePolicyCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeProviderCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeRequestCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeResourceCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeRuleCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeScopeCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeSeederCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeStructureCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeTestCommand::class,
                \Aar\LaravelModules\Console\Commands\MakeTraitCommand::class,
            ]);
        }
    }
}
