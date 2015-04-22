<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventorySkuTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('inventory_skus', function (Blueprint $table)
        {
            $table->increments('id');
            $table->timestamps();
            $table->integer('inventory_id')->unsigned();
            $table->string('code');

            $table->foreign('inventory_id')->references('id')->on('inventories')
                ->onUpdate('restrict')
                ->onDelete('cascade');

            /*
             * Make sure each SKU is unique
             */
            $table->unique(['code']);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::dropIfExists('inventory_skus');
	}
}
