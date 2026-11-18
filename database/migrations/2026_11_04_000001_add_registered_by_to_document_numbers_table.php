<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Người đăng ký hộ: Quản lý/Admin xin số giúp chuyên viên.
     * Để trống nghĩa là chuyên viên tự xin.
     */
    public function up(): void
    {
        Schema::table('document_numbers', function (Blueprint $table) {
            $table->foreignId('registered_by')->nullable()->after('requester_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_numbers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registered_by');
        });
    }
};
