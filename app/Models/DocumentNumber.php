<?php

namespace App\Models;

use App\Enums\DocumentNumberStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'number',
        'status',
        'is_backdate',
        'requester_id',
        'registered_by',
        'content',
        'signer',
        'request_date',
        'record_date',
        'expected_return_date',
        'completed_at',
        'completed_by',
        'held_by',
        'backdate_request_id',
        'last_reminder_sent_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentNumberStatus::class,
            'is_backdate' => 'boolean',
            'request_date' => 'date',
            'record_date' => 'datetime',
            'expected_return_date' => 'date',
            'completed_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    /** Số công văn hiển thị dạng 01/2026, 02/2026 ... (từ số 100 trở đi: 100/2026, 101/2026 ...) */
    public function getFormattedNumberAttribute(): string
    {
        return sprintf('%02d/%d', $this->number, $this->year);
    }

    /** Phần số, luôn 2 chữ số: 01, 02, ... 99, 100 ... */
    public function getNumberDisplayAttribute(): string
    {
        return sprintf('%02d', $this->number);
    }

    /**
     * NGÀY GHI NHẬN/NGÀY KÝ của số công văn:
     *  - Số bình thường: thời điểm hệ thống cấp số (có giờ) = record_date.
     *  - Số lùi đã cấp: ngày trong quá khứ do chuyên viên điền khi xin (chỉ có ngày) = request_date.
     *  - Số lùi còn trong kho dự trữ: chưa có.
     * Lưu ý: cột request_date luôn chứa NGÀY của mốc này với mọi số đã cấp
     * (số bình thường = ngày cấp, số lùi = ngày ký quá khứ), nên lọc/thống kê theo ngày dùng request_date.
     */
    public function getSignedAtAttribute(): ?Carbon
    {
        if ($this->status === DocumentNumberStatus::SoLuiDuTru) {
            return null;
        }

        return $this->is_backdate ? $this->request_date : $this->record_date;
    }

    /** Ngày ghi nhận/ngày ký có giờ hay không (số lùi chỉ có ngày). */
    public function getSignedHasTimeAttribute(): bool
    {
        return ! $this->is_backdate;
    }

    /** NGÀY CẤP SỐ LÙI: thời điểm quản lý duyệt cấp (chỉ có với số lùi đã cấp). */
    public function getBackdateIssuedAtAttribute(): ?Carbon
    {
        if (! $this->is_backdate || $this->status === DocumentNumberStatus::SoLuiDuTru) {
            return null;
        }

        return $this->record_date;
    }

    /**
     * Ngày ghi nhận/ngày ký dạng "dd/mm/yyyy HH:ii".
     * Số cấp trước bản cập nhật có thể không xác định được giờ (giờ = 00:00:00) -> chỉ hiện ngày.
     */
    public function getRecordDateDisplayAttribute(): ?string
    {
        if (! $this->record_date) {
            return null;
        }

        return $this->record_date->format('H:i:s') === '00:00:00'
            ? $this->record_date->format('d/m/Y')
            : $this->record_date->format('d/m/Y H:i');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** Quản lý/Admin đã đăng ký hộ số này (null = chuyên viên tự xin). */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    public function backdateRequest(): BelongsTo
    {
        return $this->belongsTo(BackdateRequest::class);
    }

    public function scopeOfYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeStatus($query, DocumentNumberStatus|string $status)
    {
        $value = $status instanceof DocumentNumberStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    public function scopeOutstanding($query)
    {
        return $query->where('status', DocumentNumberStatus::DaCap->value);
    }

    /** Số đã cấp cho người xin (Đã cấp - Chờ trả hoặc Hoàn thành), không tính số còn trong kho dự trữ. */
    public function scopeIssued($query)
    {
        return $query->whereIn('status', [DocumentNumberStatus::DaCap->value, DocumentNumberStatus::HoanThanh->value]);
    }

    public function scopeReservePool($query)
    {
        return $query->where('status', DocumentNumberStatus::SoLuiDuTru->value);
    }
}
