<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('extra_services', function (Blueprint $table) {
            $table->id('service_id');
            $table->string('service_name', 80);
            $table->unsignedTinyInteger('managed_by')->nullable();
            $table->foreign('managed_by')->references('department_id')->on('departments')->onDelete('set null');
            $table->unsignedBigInteger('account_number')->nullable();
            $table->decimal('service_fee', 10,2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extra_services');
    }
};