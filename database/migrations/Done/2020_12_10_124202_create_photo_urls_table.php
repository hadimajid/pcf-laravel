<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhotoUrlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('photo_urls', function (Blueprint $table) {
            $table->id();
            $table->string('Name');
            $table->unsignedBigInteger('ProductPhotoId');
            $table->foreign('ProductPhotoId')->references('id')->on('product_photos')->onDelete('cascade');
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
        Schema::dropIfExists('photo_urls');
    }
}
