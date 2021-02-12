<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricingExceptionListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pricing_exception_lists', function (Blueprint $table) {
            $table->id();
            $table->string('ProductNumber');
            $table->unsignedBigInteger('ProductId');
            $table->integer('Price');
            $table->integer('MAP');
            $table->foreign('ProductId')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('PriceExceptionId');
            $table->foreign('PriceExceptionId')->references('id')->on('pricing_exceptions')->onDelete('cascade');
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
        Schema::dropIfExists('pricing_exception_lists');
    }
}
