<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetricsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->integer('created_by')->unsigned()->nullable();
            $table->string('name');
            $table->string('symbol');

            $table->foreign('created_by')->references('id')->on('users')
                ->onUpdate('restrict')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('metrics');
    }
}
