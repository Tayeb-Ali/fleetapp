<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every financial operation can carry proof documents (bank transfer
     * notice, customs document, invoice, ...). The category tells which
     * operation the file belongs to; amount/currency are optional metadata
     * for the specific transaction proof.
     */
    public function up(): void
    {
        Schema::table('vehicle_attachments', function (Blueprint $table) {
            $table->string('category', 32)->default('general')->after('vin')->index();
            $table->string('note', 255)->nullable()->after('original_name');
            $table->decimal('amount', 14, 2)->nullable()->after('note');
            $table->string('currency', 8)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_attachments', function (Blueprint $table) {
            $table->dropColumn(['category', 'note', 'amount', 'currency']);
        });
    }
};
