<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requisition_forms', function (Blueprint $table) {
            // Configurable grace period (in hours) for facility cleanup
            // after the booking end_time.
            $table->unsignedTinyInteger('cleanup_hours')->default(4)->after('all_day');
        });
    }

    public function down(): void
    {
        Schema::table('requisition_forms', function (Blueprint $table) {
            $table->dropColumn('cleanup_hours');
        });
    }
};