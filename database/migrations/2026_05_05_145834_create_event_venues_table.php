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
        Schema::create('event_venues', function (Blueprint $table) {
            $table->id('event_venue_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('facility_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('event_id')->references('event_id')->on('calendar_events')->onDelete('cascade');
            $table->foreign('facility_id')->references('facility_id')->on('facilities')->onDelete('cascade');

            // Composite unique constraint to prevent duplicate venue assignments
            $table->unique(['event_id', 'facility_id'], 'unique_event_venue');

            // Indexes for query optimization
            $table->index('event_id', 'idx_event_venues_event');
            $table->index('facility_id', 'idx_event_venues_facility');
            $table->index(['event_id', 'facility_id'], 'idx_event_venues_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_venues');
    }
};