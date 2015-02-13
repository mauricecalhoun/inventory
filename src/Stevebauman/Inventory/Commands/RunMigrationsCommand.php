<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;

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
         * Call the package migration
         */
        $this->call('migrate', array('--env' => $this->option('env'), '--package' => 'stevebauman/inventory' ) );
    }

}