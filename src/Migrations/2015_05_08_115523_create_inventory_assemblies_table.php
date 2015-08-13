<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryAssembliesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('inventory_assemblies', function (Blueprint $table) {

            $table->increments('id');
            $table->timestamps();
            $table->integer('inventoryable_id');
            $table->string('inventoryable_type');
            $table->integer('inventoryable_part_id');
            $table->string('inventoryable_part_type');
            $table->integer('quantity')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('inventory_assemblies');
    }
}
