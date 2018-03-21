<?php

namespace Stevebauman\Inventory\Commands;

use Stevebauman\Inventory\Exceptions\Commands\DatabaseTableReservedException;
use Stevebauman\Inventory\Exceptions\Commands\DependencyNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Command;

/**
 * Class SchemaCheckCommand.
 */
class SchemaCheckCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'inventory:check-schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the current database to make sure the required tables are present, and the reserved tables are not';

    /**
     * Holds the database tables that
     * must be present before install.
     *
     * @var array
     */
    protected $dependencies = [
        'users' => 'Sentry, Sentinel or Laravel',
    ];

    /**
     * Holds the reserved database tables that
     * cannot exist before installation.
     *
     * @var array
     */
    protected $reserved = [
        'metrics',
        'locations',
        'categories',
        'suppliers',
        'inventory',
        'inventory_skus',
        'inventory_stocks',
        'inventory_stock_movements',
        'inventory_suppliers',
        'inventory_transactions',
        'inventory_transaction_histories',
        'inventory_assemblies',
    ];

    /**
     * Executes the console command.
     *
     * @throws DatabaseTableReservedException
     * @throws DependencyNotFoundException
     */
    public function handle()
    {
        if ($this->checkDependencies()) {
            $this->info('Schema dependencies are all good!');
        }

        if ($this->checkReserved()) {
            $this->info('Schema reserved tables are all good!');
        }
    }

    /**
     * Checks the current database for dependencies.
     *
     * @throws DependencyNotFoundException
     *
     * @return bool
     */
    private function checkDependencies()
    {
        foreach ($this->dependencies as $table => $suppliedBy) {
            if (!$this->tableExists($table)) {
                $message = sprintf('Table: %s does not exist, it is supplied by %s', $table, $suppliedBy);

                throw new DependencyNotFoundException($message);
            }
        }

        return true;
    }

    /**
     * Checks the current database for reserved tables.
     *
     * @throws DatabaseTableReservedException
     *
     * @return bool
     */
    private function checkReserved()
    {
        foreach ($this->reserved as $table) {
            if ($this->tableExists($table)) {
                $message = sprintf('Table: %s already exists. This table is reserved. Please remove the database table to continue', $table);

                throw new DatabaseTableReservedException($message);
            }
        }

        return true;
    }

    /**
     * Returns true / false if the current
     * database table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    private function tableExists($table)
    {
        return Schema::hasTable($table);
    }
}
