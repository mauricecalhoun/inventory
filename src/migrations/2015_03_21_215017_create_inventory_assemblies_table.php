<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryAssembliesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('inventory_assemblies', function (Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();

            $table->integer('inventory_id')->unsigned();
            $table->integer('stock_id')->unsigned()->nullable();
            $table->integer('part_id')->unsigned();
            $table->integer('depth')->unsigned();
            $table->decimal('quantity', 8, 2)->default(0);

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');

            $table->foreign('stock_id')->references('id')->on('inventory_stocks')->onDelete('set null');

            $table->foreign('part_id')->references('id')->on('inventories')->onDelete('cascade');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('inventory_assemblies');
	}
}
