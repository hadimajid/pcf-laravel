<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->string('ProductNumber')->nullable()->unique();
            $table->unsignedBigInteger('ProductId');
            $table->unsignedBigInteger('PriceId');

            $table->float('Price');
            $table->float('MAP');
            $table->foreign('ProductId')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('PriceId')->references('id')->on('pricing')->onDelete('cascade');

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
        Schema::dropIfExists('product_prices');
    }
}
