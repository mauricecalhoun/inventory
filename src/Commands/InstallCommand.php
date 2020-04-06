<?php

namespace Stevebauman\Inventory\Commands;

use Illuminate\Console\Command;

/**
 * Class InstallCommand.
 */
class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'inventory:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the inventory migrations';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Checking Database Schema');

        $this->call('inventory:check-schema');

        $this->info('Running migrations');

        $this->call('inventory:run-migrations');

        $this->info('Inventory has been successfully installed');
    }
}
