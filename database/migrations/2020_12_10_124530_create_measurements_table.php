<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->string('PieceName');
            $table->float('Length')->nullable();
            $table->float('Width')->nullable();
            $table->float('Depth')->nullable();
            $table->float('Height')->nullable();
            $table->float('Diameter')->nullable();
            $table->float('SeatHeight')->nullable();
            $table->float('SeatWidth')->nullable();
            $table->float('SeatDepth')->nullable();
            $table->float('Weight')->nullable();
            $table->float('DeskClearance')->nullable();
            $table->float('DepthOpen')->nullable();
            $table->float('HeightOpen')->nullable();
            $table->float('ArmHeight')->nullable();
            $table->float('ShelfDistance')->nullable();
            $table->unsignedBigInteger('ProductId')->nullable();
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
        Schema::dropIfExists('measurements');
    }
}
