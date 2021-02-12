<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRelatedProductListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('related_product_lists', function (Blueprint $table) {
            $table->id();
            $table->string('ProductNumber')->nullable();
            $table->unsignedBigInteger('RelatedProductId')->nullable();
            $table->unsignedBigInteger('ProductId');
            $table->foreign('ProductId')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('RelatedProductId')->references('id')->on('products')->onDelete('cascade');

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
        Schema::dropIfExists('related_product_lists');
    }
}
