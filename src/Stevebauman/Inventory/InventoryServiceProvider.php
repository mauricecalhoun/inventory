<?php namespace Stevebauman\Inventory;

use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		if(method_exists($this, 'package')) {

			$this->package('stevebauman/inventory');

		} else {

			$this->loadTranslationsFrom(__DIR__ . '/../../lang', 'inventory');

			$this->publishes([
				__DIR__ . '/../../config/config.php' => config_path('inventory.php'),
			], 'config');

			$this->publishes([
				__DIR__ . '/../../migrations/' => base_path('/database/migrations'),
			], 'migrations');

		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('inventory:install', function() {
			return new Commands\InstallCommand();
		});

		$this->app->bind('inventory:check-schema', function() {
			return new Commands\SchemaCheckCommand();
		});

		$this->app->bind('inventory:run-migrations', function() {
			return new Commands\RunMigrationsCommand();
		});

		$this->commands(array(
			'inventory:install',
			'inventory:check-schema',
			'inventory:run-migrations',
		));

		include __DIR__ .'/../../helpers.php';
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('inventory');
	}

}
