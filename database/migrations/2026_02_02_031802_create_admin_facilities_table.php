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
        Schema::create('admin_facilities', function (Blueprint $table) {
            $table->id('admin_facility_id')->autoIncrement();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('facility_id');
            $table->timestamps();
            // Foreign Keys
            $table->foreign('admin_id')->references('admin_id')->on('admins');
            $table->foreign('facility_id')
                  ->references('facility_id')
                  ->on('facilities')
                  ->onDelete('cascade');  // ← Add this line
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_facilities');
    }
};