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
            $table->increments('id');
            $table->timestamps();
            $table->integer('inventoryable_id');
            $table->string('inventoryable_type');
            $table->string('code');

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
