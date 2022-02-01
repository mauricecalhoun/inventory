<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryAttributesTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('display_name', 255);
            $table->tinyInteger('value_type')->unsigned()->notnull();
            $table->boolean('reserved');
            $table->enum('display_type', ['dropdown', 'string', 'currency', 'decimal', 'integer', 'date', 'exact_time']);
            $table->boolean('has_default');
            $table->timestamp('created_at')->useCurrent();
            $table
                ->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

        });

        Schema::create('attribute_values', function(Blueprint $table) {
            $table->foreignId('inventory_id');
            $table->foreignId('attribute_id');
            $table->string('string_val', 8191);
            $table->decimal('num_val', 16, 4);  // 123,456,789,012.3456
            $table->dateTime('date_val');

            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('restrict');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onUpdate('restrict');

            $table->primary(['inventory_id', 'attribute_id']);
        });

        Schema::create('attribute_default_values', function(Blueprint $table) {
            $table->foreignId('inventory_id');
            $table->foreignId('attribute_id');
            $table->string('string_val', 8191);
            $table->decimal('num_val', 16, 4);  // 123,456,789,012.3456
            $table->dateTime('date_val');
            
            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('restrict');
            $table->foreign('attribute_id')->references('id')->on('attributes')->onUpdate('restrict');

            $table->primary(['inventory_id', 'attribute_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_default_values');
    }
}
