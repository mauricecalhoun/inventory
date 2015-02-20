<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;
use Stevebauman\Inventory\InventoryServiceProvider;

class RunMigrationsCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'inventory:run-migrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the inventory migrations';

    /**
     * Execute the command
     *
     * @return void
     */
    public function fire()
    {
        /*
         * We'll check for the laravel version so we know which commands
         * to run
         */
        if(InventoryServiceProvider::$laravelVersion === 4)
        {
            /*
             * Call the package migration
             */
            $this->call('migrate', array(
                '--env' => $this->option('env'),
                '--package' => 'stevebauman/inventory'
            ));

        } else {

            $this->call('vendor:publish', array(
                '--provider' => 'Stevebauman\Inventory\InventoryServiceProvider',
                '--tag' => 'migrations'
            ));

            $this->call('migrate');

        }

    }

}