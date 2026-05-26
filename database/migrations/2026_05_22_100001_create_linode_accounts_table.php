<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('linode_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('email')->nullable();
            $table->text('api_token');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_full')->default(false);
            $table->decimal('promo_credit_usd', 10, 2)->default(100);
            $table->timestamp('promo_started_at')->nullable();
            $table->timestamp('promo_expires_at')->nullable();
            $table->decimal('balance_usd', 12, 4)->nullable();
            $table->decimal('promo_remaining_usd', 12, 4)->nullable();
            $table->decimal('reserved_monthly_usd', 12, 4)->default(0);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->text('sync_error')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('linode_accounts');
    }
};
