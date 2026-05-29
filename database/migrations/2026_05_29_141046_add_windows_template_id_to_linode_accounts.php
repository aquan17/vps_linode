<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWindowsTemplateIdToLinodeAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('linode_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('windows_template_id')->nullable()->after('api_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('linode_accounts', function (Blueprint $table) {
            $table->dropColumn('windows_template_id');
        });
    }
}
