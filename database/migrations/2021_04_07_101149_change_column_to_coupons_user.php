<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeColumnToCouponsUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('coupons_user', function (Blueprint $table) {
            $table->dropUnique('coupon_user_user_id_coupon_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupons_user', function (Blueprint $table) {
            $table->unique(['user_id','coupon_id']);
        });
    }
}
