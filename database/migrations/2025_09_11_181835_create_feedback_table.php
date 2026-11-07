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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id('feedback_id');

            // Optional email
            $table->string('email')->nullable();

            // Link to requisition_forms
            $table->unsignedBigInteger('request_id')->nullable();
            $table->foreign('request_id')
                ->references('request_id')
                ->on('requisition_forms')
                ->onDelete('cascade');   // delete feedback if requisition form is deleted

            // Ratings stored as tinyint 1–5.
            // Label mapping lives in App\Models\Feedback::RATING_LABELS — single source of truth.
            // 1 = worst, 5 = best. Nullable to allow partial submissions.
            $table->unsignedTinyInteger('system_performance')->nullable();
            $table->unsignedTinyInteger('booking_experience')->nullable();
            $table->unsignedTinyInteger('ease_of_use')->nullable();
            $table->unsignedTinyInteger('useability')->nullable();

            $table->text('additional_feedback')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
