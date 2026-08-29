<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        // RequisitionForm table indexes
        Schema::table('requisition_forms', function (Blueprint $table) {
            // Composite index for status + date range queries
            $table->index(['status_id', 'start_date', 'end_date'], 'idx_status_dates');
            $table->index(['status_id', 'created_at'], 'idx_status_created');
            $table->index('start_time');
            $table->index('end_time');
        });

        // RequisitionComment table indexes
        Schema::table('requisition_comments', function (Blueprint $table) {
            $table->index(['created_at', 'request_id'], 'idx_comments_timeline');
            $table->index('admin_id');
        });

        // Feedback table indexes
        Schema::table('feedback', function (Blueprint $table) {
            $table->index(['created_at', 'request_id'], 'idx_feedback_timeline');
        });

        // Facilities table - managed_by already exists but ensure index
        Schema::table('facilities', function (Blueprint $table) {
            $table->index('managed_by');
        });

        // Equipment table - managed_by already exists but ensure index
        Schema::table('equipment', function (Blueprint $table) {
            $table->index('managed_by');
        });
    }

    public function down()
    {
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->dropIndex('idx_status_dates');
            $table->dropIndex('idx_status_created');
            $table->dropIndex(['start_time']);
            $table->dropIndex(['end_time']);
        });

        Schema::table('requisition_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_timeline');
            $table->dropIndex(['admin_id']);
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropIndex('idx_feedback_timeline');
        });

        Schema::table('requested_facilities', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropIndex(['facility_id']);
        });

        Schema::table('requested_equipment', function (Blueprint $table) {
            $table->dropIndex(['request_id']);
            $table->dropIndex(['equipment_id']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex(['managed_by']);
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['managed_by']);
        });
    }
};
