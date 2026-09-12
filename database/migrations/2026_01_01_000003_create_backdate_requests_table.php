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
        Schema::create('backdate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();

            $table->string('content');
            $table->string('signer');
            $table->date('requested_date'); // ngày xin số (ngày quá khứ)
            $table->date('expected_return_date');

            // dang_cho | da_duyet | tu_choi
            $table->string('status')->default('dang_cho');

            $table->foreignId('document_number_id')->nullable()
                ->constrained('document_numbers')->nullOnDelete();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('reject_reason')->nullable();

            $table->timestamps();

            $table->index(['requester_id', 'status']);
        });

        Schema::table('document_numbers', function (Blueprint $table) {
            $table->foreign('backdate_request_id')
                ->references('id')->on('backdate_requests')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_numbers', function (Blueprint $table) {
            $table->dropForeign(['backdate_request_id']);
        });

        Schema::dropIfExists('backdate_requests');
    }
};
