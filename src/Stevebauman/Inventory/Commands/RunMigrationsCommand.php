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

    public function fire()
    {
        $this->call('migrate', array('--env' => $this->option('env'), '--vendor' => 'stevebauman/maintenance' ) );
    }

}