<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSerialNumber extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->text('serial')->nullable()->after('bin');
        });

        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->text('serial')->nullable()->after('returned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('inventory_stocks', function (Blueprint $table) {
            $table->dropColumn('serial');
        });

        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            $table->dropColumn('serial');
        });
    }
}
