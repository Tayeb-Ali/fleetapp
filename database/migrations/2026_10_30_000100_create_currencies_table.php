<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Client-managed currency catalogue.
     *
     * The base currency lives in settings.currency; every other currency is
     * defined here by the client (code, names, symbol, decimals, active).
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 8)->primary();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('symbol', 16)->nullable();
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('currencies')->insertOrIgnore([
            ['code' => 'USD', 'name_ar' => 'دولار أمريكي', 'name_en' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'is_active' => true, 'sort_order' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'AED', 'name_ar' => 'درهم إماراتي', 'name_en' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimals' => 2, 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'SAR', 'name_ar' => 'ريال سعودي', 'name_en' => 'Saudi Riyal', 'symbol' => '﷼', 'decimals' => 2, 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'SDG', 'name_ar' => 'جنيه سوداني', 'name_en' => 'Sudanese Pound', 'symbol' => 'ج.س', 'decimals' => 2, 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EUR', 'name_ar' => 'يورو', 'name_en' => 'Euro', 'symbol' => '€', 'decimals' => 2, 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'CNY', 'name_ar' => 'يوان صيني', 'name_en' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2, 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
