<?php

namespace Stevebauman\Inventory\Tests;

use Orchestra\Testbench\TestCase;

abstract class FunctionalTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__.'/../src/Migrations'),
        ]);
    }

    protected function getEnvironmentSetup($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders()
    {
        return [
            'Inventory' => 'Stevebauman\Inventory\InventoryServiceProvider',
        ];
    }
}
