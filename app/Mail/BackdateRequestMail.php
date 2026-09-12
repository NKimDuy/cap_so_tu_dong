<?php

namespace App\Mail;

use App\Models\BackdateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Gửi cho quản lý khi có chuyên viên xin số lùi, chờ xử lý cấp từ kho dự trữ.
 */
class BackdateRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BackdateRequest $backdateRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Cần duyệt] Yêu cầu xin số lùi từ '.$this->backdateRequest->requester?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.backdate-request',
            with: ['req' => $this->backdateRequest],
        );
    }
}
