<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWarehouseInventoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('warehouse_inventories', function (Blueprint $table) {
            $table->id();
//            $table->unsignedBigInteger('ProductId');
//            $table->foreign('ProductId')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('WarehouseId');
            $table->string('ProductNumber')->nullable()->unique();
            $table->foreign('WarehouseId')->references('id')->on('warehouses')->onDelete('restrict');
            $table->integer('QtyAvail');
            $table->unsignedBigInteger('ProductId');
            $table->foreign('ProductId')->references('id')->on('products')->onDelete('cascade');

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
        Schema::dropIfExists('warehouse_inventories');
    }
}
