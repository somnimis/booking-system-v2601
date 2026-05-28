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
        Schema::create('requested_services', function (Blueprint $table) {
            $table->id('requested_service_id');

            $table->unsignedBigInteger('request_id')->index();
            $table->unsignedBigInteger('service_id')->index();

            // Waiver columns
            $table->boolean('is_waived')->default(false);
            $table->unsignedBigInteger('waived_by')->nullable();
            $table->dateTime('waived_at')->nullable();

            // Foreign Key Constraints
            $table->foreign('request_id')
                  ->references('request_id')
                  ->on('requisition_forms')
                  ->cascadeOnDelete();

            $table->foreign('service_id')
                  ->references('service_id')
                  ->on('extra_services')
                  ->cascadeOnDelete();

            $table->foreign('waived_by')
                  ->references('admin_id')
                  ->on('admins')
                  ->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate service requests per requisition form
            $table->unique(['request_id', 'service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requested_services');
    }
};
