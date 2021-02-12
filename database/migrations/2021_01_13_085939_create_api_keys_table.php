<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_sk')->nullable();
            $table->string('stripe_pk')->nullable();
            $table->string('paypal_sk')->nullable();
            $table->string('paypal_pk')->nullable();
            $table->string('stripe_wh')->nullable();
            $table->string('paypal_wh')->nullable();
            $table->string('paypal_email')->nullable();
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
        Schema::dropIfExists('api_keys');
    }
}
