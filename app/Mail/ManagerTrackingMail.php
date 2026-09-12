<?php

namespace App\Mail;

use App\Models\DocumentNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Gửi cho quản lý để theo dõi khi có chuyên viên xin số bình thường.
 */
class ManagerTrackingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DocumentNumber $documentNumber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Theo dõi] '.$this->documentNumber->requester?->name.' vừa xin số '.$this->documentNumber->formatted_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.manager-tracking',
            with: ['doc' => $this->documentNumber],
        );
    }
}
