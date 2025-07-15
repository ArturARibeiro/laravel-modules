<?php

namespace Aar\LaravelModules;

use Illuminate\Support\ServiceProvider;

class LaravelModulesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([

            ]);
        }
    }
}