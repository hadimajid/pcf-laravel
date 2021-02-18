<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('ProductNumber');
            $table->string('Name')->nullable();
            $table->float('BoxWeight')->nullable();
            $table->float('Cubes')->nullable();
            $table->float('Qty')->nullable();
            $table->unsignedBigInteger('ProductId');
            $table->foreign('ProductId')->references('id')->on('products')->onDelete('cascade');
//            $table->unsignedBigInteger('ComponentBoxSizeId');
//            $table->foreign('ComponentBoxSizeId')->references('id')->on('component_box_sizes')->onDelete('cascade');
            $table->float('BoxLength')->nullable();
            $table->float('BoxWidth')->nullable();
            $table->float('BoxHeight')->nullable();
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
        Schema::dropIfExists('components');
    }
}
