<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_transactions', function (Blueprint $table) {
            $table->id();

            // Scan timestamps
            $table->timestamp('released_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            // Denormalized expected return moment (requisition.end_date + end_time at release).
            // Used for time-level overdue checks. Not affected by later edits to the requisition.
            $table->timestamp('expected_return_at')->nullable();

            // what item was released/returned and which request it belongs to
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('request_id')->nullable();

            // Who performed the scans - using your explicit admin_id
            $table->unsignedBigInteger('released_by')->nullable();
            $table->unsignedBigInteger('returned_by')->nullable();

            // Location tracking - can differ from request's facilities
            $table->unsignedBigInteger('facility_id')->nullable();      // Override or actual release location
            $table->string('destination_name')->nullable();              // Manual location (off-campus, no facility ID)
            $table->string('purpose_snapshot')->nullable();              // Purpose name at release time (survives renames)

            // Condition tracking (recorded on return)
            $table->unsignedTinyInteger('condition_id')->nullable(); // renamed for clarity
            $table->text('release_notes')->nullable();
            $table->text('return_notes')->nullable();

            // Availability status of the equipment container at time of transaction.
            // References availability_statuses: 1=Available, 2=Unavailable, 3=Under Maintenance, 4=Reserved, 5=Hidden.
            // NOTE: transaction lifecycle (in-flight vs. completed) is derived from returned_at, NOT this column.
            $table->unsignedTinyInteger('status_id')->default(1);

            $table->timestamps();
            $table->softDeletes();

            // === ALL FOREIGN KEYS DEFINED HERE ===

            // Core links

            $table->foreign('request_id')
                ->references('request_id')
                ->on('requisition_forms')
                ->onDelete('restrict');

            $table->foreign('item_id')
                ->references('item_id')
                ->on('equipment_items')
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

            // Availability status FK
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