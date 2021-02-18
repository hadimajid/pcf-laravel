<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipToListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ship_to_lists', function (Blueprint $table) {
            $table->id();
            $table->string('ShipToCode');
            $table->boolean('IsDefault');
            $table->string('ShipToName');
            $table->string('Address');
            $table->string('City');
            $table->string('State');
            $table->string('ZipCode');
            $table->string('CountryCode');
            $table->string('Phone');
            $table->string('Fax');
            $table->string('Email');
            $table->string('WarehouseCode');
            $table->unsignedBigInteger('WarehouseId');
            $table->foreign('WarehouseId')->references('id')->on('warehouses')->onDelete('cascade');
            $table->string('DeliveryMethod');
            $table->string('PriceCode');
            $table->string('PriceExceptionCode');
            $table->unsignedBigInteger('CustomerId');
            $table->foreign('CustomerId')->references('id')->on('customers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ship_to_lists');
    }
}
