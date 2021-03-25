<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToBillingAddresses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('billing_addresses', function (Blueprint $table) {
           $table->string('company_name')->nullable()->change();
           $table->string('country')->after('state');
           $table->string('phone')->after('country');
           $table->string('email')->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('billing_addresses', function (Blueprint $table) {
            $table->dropColumn(['country','phone','email']);
        });
    }
}
