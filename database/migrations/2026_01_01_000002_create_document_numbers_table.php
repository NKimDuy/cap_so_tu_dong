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
        Schema::create('document_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('number');

            // da_cap | hoan_thanh | so_lui_du_tru
            $table->string('status')->default('da_cap');

            // true nếu đây là số lùi (ngày xin số là ngày quá khứ)
            $table->boolean('is_backdate')->default(false);

            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('content')->nullable();
            $table->string('signer')->nullable();

            $table->date('request_date')->nullable();
            $table->date('record_date')->nullable();
            $table->date('expected_return_date')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();

            // quản lý đã giữ số lùi dự trữ này (khi còn ở kho, hoặc người tạo ban đầu)
            $table->foreignId('held_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('backdate_request_id')->nullable();

            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['year', 'number']);
            $table->index(['requester_id', 'status']);
            $table->index(['status', 'expected_return_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_numbers');
    }
};
