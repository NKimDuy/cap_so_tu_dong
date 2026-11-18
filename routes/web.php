<?php

use App\Livewire\AllNumbers;
use App\Livewire\ChangePassword;
use App\Livewire\Dashboard;
use App\Livewire\MyNumbers;
use App\Livewire\PendingBackdateRequests;
use App\Livewire\Reports;
use App\Livewire\RequestNumber;
use App\Livewire\RequestOnBehalf;
use App\Livewire\ReservePool;
use App\Livewire\SignerManagement;
use App\Livewire\SystemLogs;
use App\Livewire\SystemSettings;
use App\Livewire\UserImport;
use App\Livewire\UserManagement;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/doi-mat-khau', ChangePassword::class)->name('doi-mat-khau');

    // Tự xin số cho mình: Chuyên viên, Quản lý và Admin
    Route::middleware('role:chuyen_vien,quan_ly,admin')->group(function () {
        Route::get('/so/xin-so', RequestNumber::class)->name('so.xin-so');
        Route::get('/so/cua-toi', MyNumbers::class)->name('so.cua-toi');
    });

    // Quản lý (admin cũng được xem để giám sát)
    Route::middleware('role:quan_ly,admin')->group(function () {
        Route::get('/so/xin-so-giup', RequestOnBehalf::class)->name('so.xin-so-giup');
        Route::get('/so/duyet-so-lui', PendingBackdateRequests::class)->name('so.duyet-so-lui');
        Route::get('/so/kho-du-tru', ReservePool::class)->name('so.kho-du-tru');
        Route::get('/so/tat-ca', AllNumbers::class)->name('so.tat-ca');
        Route::get('/bao-cao', Reports::class)->name('bao-cao');
        Route::get('/nguoi-ky-so', SignerManagement::class)->name('nguoi-ky-so');
    });

    // Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/nguoi-dung', UserManagement::class)->name('nguoi-dung');
        Route::get('/nguoi-dung/nhap-file', UserImport::class)->name('nhap-tai-khoan');
        Route::get('/cau-hinh', SystemSettings::class)->name('cau-hinh');
        Route::get('/nhat-ky', SystemLogs::class)->name('nhat-ky');
    });
});

require __DIR__.'/auth.php';
