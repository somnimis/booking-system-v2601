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
        Schema::create('requisition_purposes', function (Blueprint $table) {
            $table->tinyIncrements('purpose_id');
            $table->string('purpose_name', 50)->index();
            $table->unsignedTinyInteger('routes_to')->nullable();
            $table->decimal('discount_fee', 10, 2)->nullable();
            $table->enum('discount_type', ['flat', 'percentage'])->default('flat');
            $table->foreign('routes_to')->references('department_id')->on('departments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_purposes');
    }
};