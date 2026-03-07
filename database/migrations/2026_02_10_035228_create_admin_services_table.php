<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_services', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('service_id');
            
            // Composite primary key
            $table->primary(['admin_id', 'service_id']);
            
            // Foreign keys with cascade delete
            $table->foreign('admin_id')->references('admin_id')->on('admins')->onDelete('cascade');
            $table->foreign('service_id')->references('service_id')->on('extra_services')->onDelete('cascade');
            
            // No timestamps as requested
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_services');
    }
};