<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purchase / shipping / landed / modification / sale costs with the FX
     * rate captured at transaction time.
     *
     * `duty` is renamed to `landed_cost` (legacy alias kept in
     * Vehicle::LEGACY_MAP) because the client records the customs + port
     * package as one landed cost.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('purchase_currency', 8)->nullable()->after('cost');
            $table->decimal('purchase_fx_rate', 18, 6)->nullable()->after('purchase_currency');
            $table->decimal('purchase_base_amount', 14, 2)->nullable()->after('purchase_fx_rate');

            $table->decimal('shipping_cost', 14, 2)->nullable()->after('arrived_at');
            $table->string('shipping_currency', 8)->nullable()->after('shipping_cost');
            $table->decimal('shipping_fx_rate', 18, 6)->nullable()->after('shipping_currency');
            $table->decimal('shipping_base_amount', 14, 2)->nullable()->after('shipping_fx_rate');

            $table->decimal('landed_cost', 14, 2)->nullable()->after('customs_date');
            $table->decimal('modification_cost', 14, 2)->nullable()->after('landed_cost');

            $table->string('sale_currency', 8)->nullable()->after('sale_price');
            $table->decimal('sale_fx_rate', 18, 6)->nullable()->after('sale_currency');
            $table->decimal('sale_base_amount', 14, 2)->nullable()->after('sale_fx_rate');
        });

        $base = strtoupper((string) config('fleet.currency', 'USD'));

        // Legacy rows were single-currency: assume the configured base at rate 1.
        DB::table('vehicles')->update([
            'purchase_currency' => DB::raw("coalesce(purchase_currency, '{$base}')"),
            'purchase_fx_rate' => DB::raw('coalesce(purchase_fx_rate, 1)'),
            'purchase_base_amount' => DB::raw('coalesce(purchase_base_amount, cost)'),
            'sale_currency' => DB::raw("coalesce(sale_currency, '{$base}')"),
            'sale_fx_rate' => DB::raw('coalesce(sale_fx_rate, 1)'),
            'sale_base_amount' => DB::raw('coalesce(sale_base_amount, sale_price)'),
            'landed_cost' => DB::raw('coalesce(landed_cost, duty)'),
        ]);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('duty');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->decimal('duty', 12, 2)->nullable()->after('customs_date');
        });

        DB::table('vehicles')->update([
            'duty' => DB::raw('landed_cost'),
        ]);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_currency', 'purchase_fx_rate', 'purchase_base_amount',
                'shipping_cost', 'shipping_currency', 'shipping_fx_rate', 'shipping_base_amount',
                'landed_cost', 'modification_cost',
                'sale_currency', 'sale_fx_rate', 'sale_base_amount',
            ]);
        });
    }
};
