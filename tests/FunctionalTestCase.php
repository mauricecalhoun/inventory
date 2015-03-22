<?php

use Illuminate\Database\Capsule\Manager as DB;

abstract class FunctionalTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->configureDatabase();
        $this->migrateTables();
    }

    protected function configureDatabase()
    {
        $db = new DB;

        $db->addConnection(array(
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ));

        $db->bootEloquent();

        $db->setAsGlobal();
    }

    protected function migrateTables()
    {
        $this->migrateUserTable();

        $this->migrateMetricTable();

        $this->migrateCategoryTable();

        $this->migrateLocationTable();

        $this->migrateInventoryTable();

        $this->migrateInventoryStockTable();

        $this->migrateInventoryStockMovementTable();

        $this->migrateInventorySkuTable();

        $this->migrateInventorySupplierTables();

        $this->migrateInventoryTransactionTable();

        $this->migrateInventoryTransactionHistoryTable();

        $this->migrateInventoryAssemblyColumn();

        $this->migrateInventoryAssemblyTable();
    }

    protected function migrateUserTable()
    {
        DB::schema()->create('users', function ($table)
        {
            $table->increments('id');
            $table->string('name');
        });
    }

    protected function migrateMetricTable()
    {
        DB::schema()->create('metrics', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('name');
            $table->string('symbol');

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');
        });
    }

    public function migrateCategoryTable()
    {
        DB::schema()->create('categories', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('parent_id')->nullable()->index();
            $table->integer('lft')->nullable()->index();
            $table->integer('rgt')->nullable()->index();
            $table->integer('depth')->nullable();
            $table->string('name');

            /*
             * This field is for scoping categories, use it if you
             * want to store multiple nested sets on the same table
             */
            $table->string('belongs_to')->nullable();
        });
    }

    protected function migrateLocationTable()
    {
        DB::schema()->create('locations', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('parent_id')->nullable()->index();
            $table->integer('lft')->nullable()->index();
            $table->integer('rgt')->nullable()->index();
            $table->integer('depth')->nullable();
            $table->string('name');

            /*
             * This field is for scoping categories, use it if you
             * want to store multiple nested sets on the same table
             */
            $table->string('belongs_to')->nullable();
        });
    }

    protected function migrateInventoryTable()
    {
        DB::schema()->create('inventories', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('category_id')->unsigned()->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('metric_id')->unsigned();
            $table->string('name');
            $table->text('description')->nullable();

            $table->foreign('category_id')->references('id')->on('categories')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('metric_id')->references('id')->on('metrics')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    protected function migrateInventoryStockTable()
    {
        DB::schema()->create('inventory_stocks', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('inventory_id')->unsigned();
            $table->integer('location_id')->unsigned();
            $table->decimal('quantity', 8, 2)->default(0);
            $table->string('aisle')->nullable();
            $table->string('row')->nullable();
            $table->string('bin')->nullable();

            /*
             * This allows only one stock to be created
             * on a single location
             */
            $table->unique(array('inventory_id', 'location_id'));

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('location_id')->references('id')->on('locations')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    protected function migrateInventoryStockMovementTable()
    {
        DB::schema()->create('inventory_stock_movements', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('stock_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->decimal('before', 8, 2)->default(0);
            $table->decimal('after', 8, 2)->default(0);
            $table->decimal('cost', 8, 2)->default(0)->nullable();
            $table->string('reason')->nullable();

            $table->foreign('stock_id')->references('id')->on('inventory_stocks')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');
        });
    }

    protected function migrateInventorySkuTable()
    {
        DB::schema()->create('inventory_skus', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('inventory_id')->unsigned();
            $table->string('code', 20);

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            /*
             * Make sure each SKU is unique
             */
            $table->unique(array('code'));
        });
    }

    protected function migrateInventorySupplierTables()
    {
        DB::schema()->create('suppliers', function($table)
        {
            $table->increments('id');
            $table->timestamps();

            $table->string('name');
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('contact_title')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_fax')->nullable();
            $table->string('contact_email')->nullable();
        });

        DB::schema()->create('inventory_suppliers', function ($table)
        {
            $table->increments('id');
            $table->timestamps();

            $table->integer('inventory_id')->unsigned();
            $table->integer('supplier_id')->unsigned();

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            $table->foreign('supplier_id')->references('id')->on('suppliers')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    protected function migrateInventoryTransactionTable()
    {
        DB::schema()->create('inventory_transactions', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('stock_id')->unsigned();
            $table->string('name')->nullable();
            $table->string('state');
            $table->decimal('quantity', 8, 2)->default(0);

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('stock_id')->references('id')->on('inventory_stocks')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    protected function migrateInventoryTransactionHistoryTable()
    {
        DB::schema()->create('inventory_transaction_histories', function ($table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('transaction_id')->unsigned();

            /*
             * Allows tracking states for each transaction
             */
            $table->string('state_before');
            $table->string('state_after');

            /*
             * Allows tracking the quantities of each transaction
             */
            $table->string('quantity_before');
            $table->string('quantity_after');

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

            $table->foreign('transaction_id')->references('id')->on('inventory_transactions')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    protected function migrateInventoryAssemblyColumn()
    {
        DB::schema()->table('inventories', function ($table)
        {
            $table->boolean('is_assembly')->default(false);
        });
    }

    protected function migrateInventoryAssemblyTable()
    {
        DB::schema()->create('inventory_assemblies', function ($table)
        {
            $table->increments('id');
            $table->timestamps();

            $table->integer('inventory_id')->unsigned();
            $table->integer('part_id')->unsigned();
            $table->integer('depth')->unsigned();
            $table->integer('quantity')->nullable();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');

            $table->foreign('part_id')->references('id')->on('inventories')->onDelete('cascade');
        });
    }
}