<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventorySkuTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inventory_skus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->bigInteger('inventory_id')->unsigned();
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
     */
    public function down()
    {
        Schema::dropIfExists('inventory_skus');
    }
}
