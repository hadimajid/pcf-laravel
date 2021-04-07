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
        Schema::table('coupon_user', function (Blueprint $table) {
            $table->dropForeign('coupon_user_user_id_foreign');
            $table->dropForeign('coupon_user_coupon_id_foreign');
            $table->dropUnique('coupon_user_user_id_coupon_id_unique');
            $table->dropColumn('status');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('restrict');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupon_user', function (Blueprint $table) {
            $table->enum('status',['active','expired']);
            $table->unique(['user_id','coupon_id']);

        });
    }
}
