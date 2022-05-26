<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @codeCoverageIgnore
 */
class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table
                ->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();
            $table->foreignId('parent_id')->nullable()->index();
            $table->integer('lft')->nullable()->index();
            $table->integer('rgt')->nullable()->index();
            $table->integer('depth')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('city');
            $table->string('state_province');
            $table->string('postal_code');
            $table->string('county')->nullable();
            $table->string('district')->nullable();
            $table->string('country');
            // TODO: implement time zones
            // $table->foreign('timezone_id')->references('id')->on('timezones')
            //     ->onUpdate('restrict');

            /*
             * This field is for scoping categories, use it if you
             * want to store multiple nested sets on the same table
             */
            $table->string('belongs_to')->nullable();
        });

        Schema::create('location_contacts', function(Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->foreignId('location_id')->unsigned();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            $table->string('phone')->nullable();
            $table->string('type');

            $table->foreign('location_id')->references('id')->on('locations')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
