<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The Vehicle domain owns the canonical schema below. A parallel
        // workstream (000001) creates a legacy TEXT-columns table first;
        // replace it so model, transitions and reports share one schema.
        // (Dev DB verified empty via `php artisan db:show --counts`.)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('vehicle_activities');
        Schema::dropIfExists('vehicles');
        Schema::enableForeignKeyConstraints();

        Schema::create('vehicles', function (Blueprint $table) {
            $table->string('vin')->primary();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('fuel')->nullable();
            $table->date('purchased_at')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('cost', 12, 2)->nullable();

            $table->string('bl')->nullable();
            $table->string('voyage_no')->nullable();
            $table->string('pol')->nullable();
            $table->string('pod')->nullable();
            $table->string('destination')->nullable();
            $table->date('shipped_at')->nullable();
            $table->date('arrived_at')->nullable();

            $table->string('customs_no')->nullable();
            $table->date('customs_date')->nullable();
            $table->decimal('duty', 12, 2)->nullable();

            $table->string('warehouse_location')->nullable();
            $table->date('warehouse_in_at')->nullable();

            $table->string('sale_order_no')->nullable()->unique();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_phone')->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('amount_paid', 12, 2)->nullable();
            $table->date('sold_at')->nullable();

            $table->string('receiver_name')->nullable();
            $table->date('delivered_at')->nullable();

            $table->text('remarks')->nullable();
            $table->string('stage')->default('purchased');

            $table->timestamps();
            $table->softDeletes();

            $table->index('stage');
            $table->index('voyage_no');
            $table->index('buyer_name');
            $table->index('sale_order_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
