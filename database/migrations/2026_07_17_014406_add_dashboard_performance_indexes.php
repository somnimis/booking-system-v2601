<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Requisition forms — status + date range, status + created, and time-of-day filters
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->index(['status_id', 'start_date', 'end_date'], 'idx_status_dates');
            $table->index(['status_id', 'created_at'], 'idx_status_created');
            $table->index('start_time', 'idx_requisition_start_time');
            $table->index('end_time', 'idx_requisition_end_time');
        });

        // Requisition comments — dashboard timeline and per-admin lookups
        Schema::table('requisition_comments', function (Blueprint $table) {
            $table->index(['created_at', 'request_id'], 'idx_comments_timeline');
            $table->index('admin_id', 'idx_comments_admin');
        });

        // Feedback — dashboard timeline
        Schema::table('feedback', function (Blueprint $table) {
            $table->index(['created_at', 'request_id'], 'idx_feedback_timeline');
        });
    }

    public function down(): void
    {
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->dropIndex('idx_status_dates');
            $table->dropIndex('idx_status_created');
            $table->dropIndex('idx_requisition_start_time');
            $table->dropIndex('idx_requisition_end_time');
        });

        Schema::table('requisition_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_timeline');
            $table->dropIndex('idx_comments_admin');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropIndex('idx_feedback_timeline');
        });
    }
};