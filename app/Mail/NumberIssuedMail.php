<?php

namespace App\Mail;

use App\Models\DocumentNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Gửi cho chuyên viên ngay sau khi hệ thống cấp số thành công
 * (áp dụng cho cả xin số bình thường và số lùi được duyệt).
 */
class NumberIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DocumentNumber $documentNumber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Số công văn '.$this->documentNumber->formatted_number.' đã được cấp',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.number-issued',
            with: ['doc' => $this->documentNumber],
        );
    }
}
