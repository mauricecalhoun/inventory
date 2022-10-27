<?php

namespace Trexology\Inventory;

use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider
{
    /**
     * Inventory version.
     *
     * @var string
     */
    const VERSION = '1.8.0';

    public function boot() {
      // Load the inventory translations from the inventory lang folder
      $this->loadTranslationsFrom(__DIR__.'/Lang', 'inventory');
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        // Assign the configuration as publishable, and tag it as 'config'
        $this->publishes([
            __DIR__.'/Config/config.php' => config_path('inventory.php'),
        ], 'config');

        // Assign the migrations as publishable, and tag it as 'migrations'
        $this->publishes([
            __DIR__.'/Migrations/' => base_path('database/migrations'),
        ], 'migrations');
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return ['inventory'];
    }
}
