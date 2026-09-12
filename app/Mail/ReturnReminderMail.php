<?php

namespace App\Mail;

use App\Models\DocumentNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Nhắc chuyên viên trả số công văn (gửi đúng ngày dự kiến trả, sau đó lặp mỗi tuần).
 */
class ReturnReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DocumentNumber $documentNumber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Nhắc trả số] Số công văn '.$this->documentNumber->formatted_number.' đã đến hạn trả',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.return-reminder',
            with: ['doc' => $this->documentNumber],
        );
    }
}
