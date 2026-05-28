<?php
// database/migrations/2026_05_26_214841_add_dashboard_indexes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add indexes safely using raw SQL with IF NOT EXISTS
        try {
            // Index for today's reservations (status_id + start_date)
            DB::statement("
                CREATE INDEX IF NOT EXISTS requisition_forms_status_id_start_date_index 
                ON requisition_forms (`status_id`, `start_date`)
            ");
        } catch (\Exception $e) {
            // Index might already exist or syntax not supported
        }
        
        try {
            // Index for is_finalized
            DB::statement("
                CREATE INDEX IF NOT EXISTS requisition_forms_is_finalized_index 
                ON requisition_forms (`is_finalized`)
            ");
        } catch (\Exception $e) {
            // Index might already exist or syntax not supported
        }
        
        // Feedback table indexes
        try {
            DB::statement("
                CREATE INDEX IF NOT EXISTS feedback_request_id_created_at_index 
                ON feedback (`request_id`, `created_at`)
            ");
        } catch (\Exception $e) {
            // Index might already exist or syntax not supported
        }
        
        // Requisition approvals indexes
        if (Schema::hasTable('requisition_approvals')) {
            try {
                DB::statement("
                    CREATE INDEX IF NOT EXISTS requisition_approvals_status_stage_index 
                    ON requisition_approvals (`status`, `stage`)
                ");
            } catch (\Exception $e) {
                // Index might already exist or syntax not supported
            }
            
            try {
                DB::statement("
                    CREATE INDEX IF NOT EXISTS requisition_approvals_acted_at_index 
                    ON requisition_approvals (`acted_at`)
                ");
            } catch (\Exception $e) {
                // Index might already exist or syntax not supported
            }
        }
    }
    
    public function down(): void
    {
        // Drop indexes (IF EXISTS prevents errors)
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->dropIndexIfExists('requisition_forms_status_id_start_date_index');
            $table->dropIndexIfExists('requisition_forms_is_finalized_index');
        });
        
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropIndexIfExists('feedback_request_id_created_at_index');
        });
        
        if (Schema::hasTable('requisition_approvals')) {
            Schema::table('requisition_approvals', function (Blueprint $table) {
                $table->dropIndexIfExists('requisition_approvals_status_stage_index');
                $table->dropIndexIfExists('requisition_approvals_acted_at_index');
            });
        }
    }
};