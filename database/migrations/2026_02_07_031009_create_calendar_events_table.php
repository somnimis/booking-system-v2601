<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->bigIncrements('event_id'); // PK auto-increment
            $table->string('event_name');
            $table->enum('event_type', ['hall_booking', 'school_event', 'holiday'])->default('hall_booking');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->time('start_time');
            $table->date('end_date');
            $table->time('end_time');
            $table->boolean('all_day')->default(false);
            $table->timestamps();

            // === Indexes === //
            $table->index(['event_type']);                  // for filtering by type
            $table->index(['event_name']);                  // for searching by name
            $table->index(['start_date', 'end_date']);      // for date range queries (calendar view)

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
