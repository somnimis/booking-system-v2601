<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * consumed by CheckAvailabilityService::checkServiceAvailability().
     */
    public function up(): void
    {
        Schema::create('event_services', function (Blueprint $table) {
            $table->id('event_service_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('service_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('event_id')->references('event_id')->on('calendar_events')->onDelete('cascade');
            $table->foreign('service_id')->references('service_id')->on('extra_services')->onDelete('cascade');

            // Composite unique constraint to prevent duplicate service assignments
            $table->unique(['event_id', 'service_id'], 'unique_event_service');

            // Indexes for query optimization
            $table->index('event_id', 'idx_event_services_event');
            $table->index('service_id', 'idx_event_services_service');
            $table->index(['event_id', 'service_id'], 'idx_event_services_composite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_services');
    }
};