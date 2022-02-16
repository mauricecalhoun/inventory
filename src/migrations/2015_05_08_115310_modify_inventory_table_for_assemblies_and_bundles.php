<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @codeCoverageIgnore
 */
class ModifyInventoryTableForAssembliesAndBundles extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->boolean('is_assembly')->default(false);
            $table->boolean('is_bundle')->default(false);
            $table->boolean('is_parent')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('is_assembly');
            $table->dropColumn('is_bundle');
            $table->dropColumn('is_parent');
        });
    }
}
