<?php

namespace App\Mail;

use App\Models\BackdateRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Gửi cho chuyên viên khi yêu cầu xin số lùi bị quản lý từ chối.
 */
class BackdateRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public BackdateRequest $backdateRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu xin số lùi của bạn đã bị từ chối',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.backdate-rejected',
            with: ['req' => $this->backdateRequest],
        );
    }
}
