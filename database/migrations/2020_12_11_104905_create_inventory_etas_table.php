<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryEtasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_etas', function (Blueprint $table) {
            $table->id();
            $table->integer('Qty');
            $table->date('Eta');
            $table->unsignedBigInteger('WarehouseInventoryId');
            $table->foreign('WarehouseInventoryId')->references('id')->on('warehouse_inventories')->onDelete('cascade');
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
        Schema::dropIfExists('inventory_etas');
    }
}
