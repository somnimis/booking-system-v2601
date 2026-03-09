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
        Schema::create('department_facilities', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('department_id');
            $table->unsignedBigInteger('facility_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('department_id')->references('department_id')->on('departments')->onDelete('cascade');
            $table->foreign('facility_id')->references('facility_id')->on('facilities')->onDelete('cascade');

            // Optional: prevent duplicate pairs
            $table->unique(['department_id', 'facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_facilities');
    }
};