<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Requisition forms — dashboard "today's reservations" and finalize queries
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->index(['status_id', 'start_date'], 'idx_requisition_status_start');
            $table->index('is_finalized', 'idx_requisition_is_finalized');
        });

        // Feedback — dashboard trends and per-request lookups
        Schema::table('feedback', function (Blueprint $table) {
            $table->index(['request_id', 'created_at'], 'idx_feedback_request_created');
        });

        // Requisition approvals — pending queue and acted-on timestamps
        Schema::table('requisition_approvals', function (Blueprint $table) {
            $table->index(['status', 'stage'], 'idx_approvals_status_stage');
            $table->index('acted_at', 'idx_approvals_acted_at');
        });
    }

    public function down(): void
    {
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->dropIndex('idx_requisition_status_start');
            $table->dropIndex('idx_requisition_is_finalized');
        });

        Schema::table('feedback', function (Blueprint $table) {
            $table->dropIndex('idx_feedback_request_created');
        });

        Schema::table('requisition_approvals', function (Blueprint $table) {
            $table->dropIndex('idx_approvals_status_stage');
            $table->dropIndex('idx_approvals_acted_at');
        });
    }
};