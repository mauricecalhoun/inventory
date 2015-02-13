<?php

use Illuminate\Database\Capsule\Manager as DB;

abstract class FunctionalTestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
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

    public function migrateTables()
    {
        DB::schema()->create('users', function ($table) {

            $table->increments('id');
            $table->string('name');

        });

        DB::schema()->create('metrics', function ($table) {

            $table->increments('id');
            $table->timestamps();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('name');
            $table->string('symbol');

            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');

        });

        DB::schema()->create('categories', function ($table) {

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

        DB::schema()->create('locations', function ($table) {

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

        DB::schema()->create('inventories', function ($table) {

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

        DB::schema()->create('inventory_stocks', function ($table) {

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

        DB::schema()->create('inventory_stock_movements', function ($table) {

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
}