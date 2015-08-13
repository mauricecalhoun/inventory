<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inventory_stocks', function (Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();

            $table->integer('stockable_id');
            $table->string('stockable_type');

            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('location_id')->unsigned();
            $table->string('aisle')->nullable();
            $table->string('row')->nullable();
            $table->string('bin')->nullable();

            // This allows only one inventory stock
            // to be created on a single location
            $table->unique(['stockable_id', 'stockable_type', 'location_id']);

            $table->foreign('user_id')->references('id')->on('users');

            $table->foreign('location_id')->references('id')->on('locations');
        });

        Schema::create('inventory_stock_movements', function (Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->softDeletes();

            $table->integer('stock_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->decimal('before', 8, 2)->default(0);
            $table->decimal('after', 8, 2)->default(0);
            $table->decimal('cost', 8, 2)->default(0)->nullable();
            $table->string('reason')->nullable();

            $table->foreign('stock_id')->references('id')->on('inventory_stocks');

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('inventory_stock_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('inventories');
    }
}
