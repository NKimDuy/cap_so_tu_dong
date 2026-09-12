<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nhắc trả số công văn: chạy mỗi ngày lúc 08:00 sáng.
// Yêu cầu: Task Scheduler (Windows) hoặc cron (Linux) phải gọi "php artisan schedule:run" mỗi phút.
// Xem hướng dẫn chi tiết trong HUONG_DAN_CAI_DAT.md
Schedule::command('so:nhac-tra-so')->dailyAt('08:00');
