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
        Schema::create('department_equipment', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('department_id');
            $table->unsignedBigInteger('equipment_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('equipment_id')->references('equipment_id')->on('equipment')->onDelete('cascade');

            // Prevent duplicate assignments
            $table->unique(['department_id', 'equipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_equipment');
    }
};