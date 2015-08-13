<?php

namespace Stevebauman\Inventory;

use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Inventory version.
     *
     * @var string
     */
    const VERSION = '2.0';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the service provider.
     */
    public function boot()
    {
        // Load the inventory translations from the inventory lang folder
        $this->loadTranslationsFrom(__DIR__.'/Lang', 'inventory');

        // Assign the configuration as publishable, and tag it as 'config'
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('inventory.php'),
        ], 'config');

        // Assign the migrations as publishable, and tag it as 'migrations'
        $this->publishes([
            __DIR__.'/Migrations/' => base_path('database/migrations'),
        ], 'migrations');
    }

    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['inventory'];
    }
}
