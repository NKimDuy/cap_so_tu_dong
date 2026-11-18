<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột "Ngày ghi nhận/Ngày ký" (record_date) trước đây chỉ lưu ngày, nay lưu cả ngày và giờ.
     *
     * Với dữ liệu đã có, giờ được lấy lại từ thời điểm thật đã lưu trong hệ thống:
     *  - Số lùi đã được duyệt: thời điểm quản lý duyệt (backdate_requests.approved_at).
     *  - Các số còn lại: thời điểm tạo số (document_numbers.created_at).
     * Chỉ điền khi thời điểm đó trùng ngày với record_date cũ, để không làm sai ngày đã ghi nhận.
     * Trường hợp không xác định được giờ thì giữ nguyên ngày (giờ 00:00:00, giao diện chỉ hiện ngày).
     */
    public function up(): void
    {
        Schema::table('document_numbers', function (Blueprint $table) {
            $table->dateTime('record_date')->nullable()->change();
        });

        $rows = DB::table('document_numbers')
            ->leftJoin('backdate_requests', 'backdate_requests.id', '=', 'document_numbers.backdate_request_id')
            ->whereNotNull('document_numbers.record_date')
            ->select([
                'document_numbers.id',
                'document_numbers.record_date',
                'document_numbers.created_at',
                'backdate_requests.approved_at',
            ])
            ->get();

        foreach ($rows as $row) {
            $recordDay = substr((string) $row->record_date, 0, 10);
            $exact = null;

            foreach ([$row->approved_at, $row->created_at] as $candidate) {
                if ($candidate && substr((string) $candidate, 0, 10) === $recordDay) {
                    $exact = substr((string) $candidate, 0, 19);
                    break;
                }
            }

            if ($exact) {
                DB::table('document_numbers')->where('id', $row->id)->update(['record_date' => $exact]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('document_numbers', function (Blueprint $table) {
            $table->date('record_date')->nullable()->change();
        });
    }
};
