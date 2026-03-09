<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('requested_equipment_id');

            // Scan timestamps
            $table->timestamp('released_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            // Who performed the scans - using your explicit admin_id
            $table->unsignedBigInteger('released_by')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();

            // Location tracking
            $table->unsignedBigInteger('facility_id')->nullable();
            // Manual location tracking
            $table->string('destination_name')->nullable();

            // Condition tracking
            $table->unsignedTinyInteger('condition_id')->nullable(); // renamed for clarity
            $table->text('release_notes')->nullable();
            $table->text('return_notes')->nullable();

            // Status tracking
            $table->unsignedTinyInteger('status_id')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // === ALL FOREIGN KEYS DEFINED HERE ===
            
            // Core links
                
            $table->foreign('requested_equipment_id')
                ->references('requested_equipment_id')
                ->on('requested_equipment')
                ->onDelete('restrict');

            // Admin scanners
            $table->foreign('released_by')
                ->references('admin_id')
                ->on('admins')
                ->onDelete('set null');
                
            $table->foreign('returned_by')
                ->references('admin_id')
                ->on('admins')
                ->onDelete('set null');

            // Location
            $table->foreign('facility_id')
                ->references('facility_id')
                ->on('facilities')
                ->onDelete('set null');

            // Condition
            $table->foreign('condition_id')
                ->references('condition_id')
                ->on('conditions')
                ->onDelete('set null');

            // Status
            $table->foreign('status_id')
                ->references('status_id')
                ->on('availability_statuses')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_transactions');
    }
};