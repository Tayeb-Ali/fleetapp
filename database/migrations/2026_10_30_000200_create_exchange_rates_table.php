<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monthly exchange rates.
     *
     * `rate` is expressed as units of the foreign currency per 1 unit of the
     * base currency (e.g. 1 USD = 3.6725 AED). Conversion to base is
     * amount / rate.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 8)->index();
            $table->string('period', 7)->index();
            $table->decimal('rate', 18, 6);
            $table->timestamps();

            $table->unique(['currency', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
