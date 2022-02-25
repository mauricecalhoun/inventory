<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;

/**
 * Class PublishMigrations.
 * 
 * @codeCoverageIgnore
 */
class PublishMigrationsCommand extends Command
{

	/**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'inventory:publish-migrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the inventory migrations to the database/migrations path';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

	/**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Publishing Inventory Migrations to the database/migrations directory');

		$this->call('vendor:publish', [
			'--provider' => 'Stevebauman\Inventory\InventoryServiceProvider',
			'--tag' => 'migrations',
            '--force'
		]);

        $this->info('Inventory Migrations has been successfully published');
    }

}