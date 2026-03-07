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
    Schema::create('signatures', function (Blueprint $table) {
        $table->bigIncrements('signature_id');

        // What’s being signed
        $table->unsignedBigInteger('requisition_id');
        $table->foreign('requisition_id')
            ->references('request_id')
            ->on('requisition_forms')
            ->cascadeOnDelete();

        // Who signed it (admin)
        $table->unsignedBigInteger('signatory_id');
        $table->foreign('signatory_id')
            ->references('admin_id')
            ->on('admins')
            ->cascadeOnDelete();

        // Signature payload
        $table->longText('signature_data');

        // PH compliance / integrity
        $table->char('document_hash', length: 64); // SHA-256
        $table->char('verification_code', 32)->unique();

        // Legal timestamps
        $table->timestamp('signed_at')->useCurrent();
        $table->timestamp('verified_at')->nullable();

        // Audit trail
        $table->ipAddress('ip_address');
        $table->text('user_agent')->nullable();
        $table->enum('device_type', ['mobile', 'tablet', 'desktop'])->nullable();

        // State
        $table->enum('status', ['pending', 'signed', 'revoked'])
            ->default('signed');

        // Indexes
        $table->index(['requisition_id', 'signatory_id']);
        $table->index('signed_at');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
