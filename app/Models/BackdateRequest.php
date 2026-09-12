<?php

namespace App\Models;

use App\Enums\BackdateRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackdateRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'content',
        'signer',
        'requested_date',
        'expected_return_date',
        'status',
        'document_number_id',
        'approved_by',
        'approved_at',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => BackdateRequestStatus::class,
            'requested_date' => 'date',
            'expected_return_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documentNumber(): BelongsTo
    {
        return $this->belongsTo(DocumentNumber::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', BackdateRequestStatus::DangCho->value);
    }
}
