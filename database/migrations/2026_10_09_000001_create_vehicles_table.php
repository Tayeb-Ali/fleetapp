<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            return;
        }

        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->text('brand')->nullable();
            $table->text('model')->nullable();
            $table->text('fuel')->nullable();
            $table->text('mod')->nullable();
            $table->text('pdate')->nullable();
            $table->text('supplier')->nullable();
            $table->text('cost')->nullable();
            $table->text('bl')->nullable();
            $table->text('voy')->nullable();
            $table->text('pol')->nullable();
            $table->text('pod')->nullable();
            $table->text('dest')->nullable();
            $table->text('ship')->nullable();
            $table->text('arr')->nullable();
            $table->text('cust_no')->nullable();
            $table->text('cust_date')->nullable();
            $table->text('duty')->nullable();
            $table->text('loc')->nullable();
            $table->text('in_date')->nullable();
            $table->text('ord_no')->nullable();
            $table->text('client')->nullable();
            $table->text('phone')->nullable();
            $table->text('price')->nullable();
            $table->text('paid')->nullable();
            $table->text('sale_date')->nullable();
            $table->text('recv')->nullable();
            $table->text('del_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('stage', 32)->default('purchased')->index();
            $table->text('updated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
