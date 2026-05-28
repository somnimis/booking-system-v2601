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
        Schema::create('facilities', function (Blueprint $table) {

            $table->id('facility_id');
            $table->unsignedBigInteger('parent_facility_id')->nullable();

            // for rooms
            $table->unsignedTinyInteger('floor_level')->default(1)->nullable();

            // for buildings 
            $table->string('facility_code', 20)->nullable();
            $table->unsignedTinyInteger('total_levels')->default(1)->nullable();

            // general details
            $table->string('facility_name', 50);
            $table->string('description', 250)->default('No description provided for this facility.');
            $table->unsignedInteger('maximum_rental_hour')->nullable();
            $table->unsignedTinyInteger('category_id')->default(1);
            $table->unsignedTinyInteger('subcategory_id')->nullable();
            $table->string('location_note', 200)->default('No location note provided.');
            $table->unsignedInteger('capacity')->default(1);
            $table->enum('location_type', ['Indoors', 'Outdoors']);

            // venue ownership
            $table->unsignedTinyInteger('managed_by')->nullable();
            // account number
            $table->unsignedBigInteger('account_number')->nullable();

            // fees and rates
            $table->decimal('base_fee', 10, 2);
            $table->enum('rate_type', ['Per Hour', 'Per Event'])->default('Per Hour');
            $table->unsignedTinyInteger('status_id');

            // Timestamps
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->dateTime('last_booked_at')->nullable();

            // Foreign key constraints 
            $table->foreign('parent_facility_id')->references('facility_id')->on('facilities')->onDelete('cascade');
            $table->foreign('managed_by')->references('department_id')->on('departments')->onDelete('set null');
            $table->foreign('category_id')->references('category_id')->on('facility_categories');
            $table->foreign('subcategory_id')->references('subcategory_id')->on('facility_subcategories');
            $table->foreign('status_id')->references('status_id')->on('availability_statuses');
            $table->foreign('created_by')->references('admin_id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by')->references('admin_id')->on('admins')->onDelete('set null');
            $table->foreign('deleted_by')->references('admin_id')->on('admins')->onDelete('set null');

            // Composite indexes for queries 
            $table->index(['category_id', 'subcategory_id'], 'idx_facility_category_subcategory');
            $table->index(['parent_facility_id'], 'idx_facility_parent');
            $table->index(['managed_by', 'status_id'], 'idx_facility_managed_by_status');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
