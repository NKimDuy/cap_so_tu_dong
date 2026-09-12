<?php

namespace App\Models;

use App\Enums\DocumentNumberStatus;
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
            'record_date' => 'date',
            'expected_return_date' => 'date',
            'completed_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
        ];
    }

    /** Số công văn hiển thị dạng 001/2026, 002/2026 ... */
    public function getFormattedNumberAttribute(): string
    {
        return sprintf('%03d/%d', $this->number, $this->year);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
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

    public function scopeReservePool($query)
    {
        return $query->where('status', DocumentNumberStatus::SoLuiDuTru->value);
    }
}
