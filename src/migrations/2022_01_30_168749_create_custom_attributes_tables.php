<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @codeCoverageIgnore
 */
class CreateCustomAttributesTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('custom_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('display_name', 255);
            $table->string('value_type', 6)->notnull();
            $table->boolean('reserved');
            $table->enum('display_type', ['dropdown', 'string', 'currency', 'decimal', 'integer', 'date', 'time']);
            $table->boolean('has_default');
            $table->timestamp('created_at')->useCurrent();
            $table
                ->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

        });

        Schema::create('custom_attribute_values', function(Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id');
            $table->foreignId('custom_attribute_id');
            $table->string('string_val', 8191)->nullable();
            $table->decimal('num_val', 16, 4)->nullable();  // 123,456,789,012.3456
            $table->dateTime('date_val')->nullable();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('restrict');
            $table->foreign('custom_attribute_id')->references('id')->on('custom_attributes')->onUpdate('restrict');

            $table->unique(['inventory_id', 'custom_attribute_id'], 'values_inventory_attribute_id_unique');
        });

        Schema::create('custom_attribute_defaults', function(Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id');
            $table->foreignId('custom_attribute_id');
            $table->string('string_val', 8191)->nullable();
            $table->decimal('num_val', 16, 4)->nullable();  // 123,456,789,012.3456
            $table->dateTime('date_val')->nullable();
            
            $table->foreign('inventory_id')->references('id')->on('inventories')->onUpdate('restrict');
            $table->foreign('custom_attribute_id')->references('id')->on('custom_attributes')->onUpdate('restrict');

            $table->unique(['inventory_id', 'custom_attribute_id'], 'defaults_inventory_attribute_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('custom_attribute_values');
        Schema::dropIfExists('custom_attribute_defaults');
        Schema::dropIfExists('custom_attributes');
    }
}
