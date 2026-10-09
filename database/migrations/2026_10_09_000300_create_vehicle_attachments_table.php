<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors legacy files table (server.js line 31):
     * files(id, vin, name, size, uname, ts) + stored data/uploads.
     * Laravel version stores on private disk vehicle-attachments/{vin}/.
     */
    public function up(): void
    {
        Schema::create('vehicle_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('vin')->index();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_attachments');
    }
};
