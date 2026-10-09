<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_activities', function (Blueprint $table) {
            $table->id();
            $table->string('vin');
            $table->foreign('vin')->references('vin')->on('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('from_stage')->nullable();
            $table->string('to_stage')->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('vin');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_activities');
    }
};
