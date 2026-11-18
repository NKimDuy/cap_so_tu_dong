<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Gửi 1 email thử để kiểm tra cấu hình MAIL_* trong file .env.
 * Ví dụ: php artisan mail:gui-thu ten@oude.edu.vn
 */
class SendTestMail extends Command
{
    protected $signature = 'mail:gui-thu {email : Địa chỉ nhận email thử}';

    protected $description = 'Gửi một email thử để kiểm tra cấu hình gửi email (SMTP)';

    public function handle(): int
    {
        $to = $this->argument('email');
        $mailer = config('mail.default');

        $this->line("Đang gửi bằng mailer: {$mailer}"
            .($mailer === 'smtp' ? ' ('.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').')' : '')
            .', người gửi: '.config('mail.from.address'));

        try {
            Mail::raw(
                "Đây là email thử từ hệ thống Cấp Số Tự Động.\nNếu bạn nhận được email này thì cấu hình gửi email đã đúng.",
                fn ($message) => $message->to($to)->subject('[Thử] Email từ hệ thống Cấp Số Tự Động')
            );
        } catch (\Throwable $e) {
            $this->error('Gửi thất bại: '.$e->getMessage());
            $this->line('Kiểm tra lại các dòng MAIL_* trong .env, rồi chạy: php artisan config:clear');

            return self::FAILURE;
        }

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER đang là "log": email KHÔNG gửi thật, chỉ ghi vào storage/logs/laravel.log.');
        } else {
            $this->info("Đã gửi tới {$to}. Kiểm tra hộp thư (cả mục Spam/Thư rác).");
        }

        return self::SUCCESS;
    }
}
