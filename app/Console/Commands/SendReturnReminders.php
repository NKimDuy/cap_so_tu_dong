<?php

namespace App\Console\Commands;

use App\Enums\SystemLogAction;
use App\Mail\ReturnReminderMail;
use App\Models\DocumentNumber;
use App\Models\SystemLog;
use App\Services\DocumentNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReturnReminders extends Command
{
    protected $signature = 'so:nhac-tra-so';

    protected $description = 'Gửi email nhắc trả số công văn đến hạn (và lặp lại mỗi tuần cho đến khi hoàn thành)';

    public function handle(DocumentNumberService $service): int
    {
        $interval = $service->reminderIntervalDays();

        $docs = DocumentNumber::outstanding()
            ->whereDate('expected_return_date', '<=', now()->toDateString())
            ->where(function ($q) use ($interval) {
                $q->whereNull('last_reminder_sent_at')
                    ->orWhereDate('last_reminder_sent_at', '<=', now()->subDays($interval)->toDateString());
            })
            ->with('requester')
            ->get();

        $count = 0;

        foreach ($docs as $doc) {
            if (! $doc->requester) {
                continue;
            }

            try {
                Mail::to($doc->requester->email)->send(new ReturnReminderMail($doc));
                $doc->update(['last_reminder_sent_at' => now()]);

                SystemLog::record(
                    null,
                    SystemLogAction::NhacTraSo,
                    "Đã gửi email nhắc trả số {$doc->formatted_number} đến {$doc->requester->name}.",
                    ['document_number_id' => $doc->id]
                );

                $count++;
            } catch (\Throwable $e) {
                report($e);
                $this->error("Lỗi gửi mail nhắc trả số {$doc->formatted_number}: {$e->getMessage()}");
            }
        }

        $this->info("Đã gửi {$count} email nhắc trả số.");

        return self::SUCCESS;
    }
}
