<?php

namespace App\Livewire;

use App\Enums\BackdateRequestStatus;
use App\Enums\DocumentNumberStatus;
use App\Enums\UserRole;
use App\Models\BackdateRequest;
use App\Models\DocumentNumber;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $year = now()->year;
        $data = [];

        if ($user->isChuyenVien()) {
            $data['outstanding'] = $user->outstandingNumbersCount();
            $data['maxPending'] = app(\App\Services\DocumentNumberService::class)->maxPending();
            $data['totalThisYear'] = DocumentNumber::where('requester_id', $user->id)->where('year', $year)->count();
            $data['pendingBackdate'] = BackdateRequest::where('requester_id', $user->id)
                ->where('status', BackdateRequestStatus::DangCho->value)->count();
            $data['recent'] = DocumentNumber::where('requester_id', $user->id)
                ->latest('id')->limit(5)->get();
        }

        if ($user->isManagerLike()) {
            $data['pendingApprovals'] = BackdateRequest::where('status', BackdateRequestStatus::DangCho->value)->count();
            $data['reserveCount'] = DocumentNumber::reservePool()->count();
            $data['issuedThisMonth'] = DocumentNumber::whereYear('record_date', now()->year)
                ->whereMonth('record_date', now()->month)->count();
            $data['overdue'] = DocumentNumber::outstanding()
                ->where('expected_return_date', '<', now()->toDateString())
                ->with('requester')->orderBy('expected_return_date')->limit(8)->get();
        }

        if ($user->isAdmin()) {
            $data['totalUsers'] = User::count();
            $data['totalThisYearAll'] = DocumentNumber::where('year', $year)->count();
            $data['activeUsers'] = User::active()->count();
        }

        return view('livewire.dashboard', $data);
    }
}
