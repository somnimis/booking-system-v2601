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

        Schema::create('requisition_approvals', function (Blueprint $table) {

            $table->id('approval_id');

            // booking request needing action
            $table->unsignedBigInteger('request_id');

            // required signatory for display
            $table->unsignedBigInteger('admin_id');

            // after signatory takes action (references admin_id as well)
            $table->unsignedBigInteger('acted_by')->nullable();

            //timestamps
            $table->timestamp('acted_at')->nullable();
            
            // stage field (1, 2, or 3)
            $table->tinyInteger('stage')->unsigned()->comment('1, 2, or 3');
            
            // status field
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

            $table->string('remarks', 255)->nullable();

            $table->dateTime('date_updated')->useCurrent();

            // Foreign Keys
            $table->foreign('request_id')->references('request_id')->on('requisition_forms')->onDelete('cascade');
            $table->foreign('admin_id')->references('admin_id')->on('admins')->onDelete('cascade');
            $table->foreign('acted_by')->references('admin_id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisition_approvals');
    }
};