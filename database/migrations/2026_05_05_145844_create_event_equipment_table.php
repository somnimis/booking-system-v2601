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
        Schema::create('event_equipment', function (Blueprint $table) {
            $table->id('event_equipment_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('equipment_id');
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('event_id')->references('event_id')->on('calendar_events')->onDelete('cascade');
            $table->foreign('equipment_id')->references('equipment_id')->on('equipment')->onDelete('cascade');

            // Composite unique constraint to prevent duplicate equipment assignments
            $table->unique(['event_id', 'equipment_id'], 'unique_event_equipment');

            // Indexes for query optimization
            $table->index('event_id', 'idx_event_equipment_event');
            $table->index('equipment_id', 'idx_event_equipment_equipment');
            $table->index(['event_id', 'equipment_id'], 'idx_event_equipment_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_equipment');
    }
};