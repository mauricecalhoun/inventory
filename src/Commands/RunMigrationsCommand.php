<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;

/**
 * Class RunMigrationsCommand.
 */
class RunMigrationsCommand extends Command
{
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
     * Execute the command.
     */
    public function handle()
    {
        $this->call('migrate', [
            '--path' => 'vendor/stevebauman/inventory/src/migrations',
        ]);
    }
}
