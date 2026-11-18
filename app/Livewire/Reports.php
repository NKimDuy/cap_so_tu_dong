<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Reports extends Component
{
    public int $year;
    public string $fromDate = '';
    public string $toDate = '';

    public function mount(): void
    {
        $this->year = now()->year;
    }

    public function render()
    {
        // Thống kê theo NGÀY GHI NHẬN/NGÀY KÝ: cột request_date chứa đúng ngày này cho mọi số đã cấp
        // (số bình thường = ngày cấp, số lùi = ngày ký trong quá khứ). Số còn trong kho dự trữ không tính.
        $yearQuery = DocumentNumber::issued()->whereYear('request_date', $this->year);

        $totalThisYear = (clone $yearQuery)->count();

        $byMonth = (clone $yearQuery)
            ->pluck('request_date')
            ->countBy(fn ($date) => Carbon::parse($date)->month);

        $monthly = collect(range(1, 12))->map(fn ($m) => [
            'month' => $m,
            'total' => $byMonth[$m] ?? 0,
        ]);

        $outstanding = DocumentNumber::outstanding()
            ->with('requester')
            ->orderBy('expected_return_date')
            ->get();

        $topRequestersQuery = DocumentNumber::issued()->whereNotNull('requester_id');

        if ($this->fromDate) {
            $topRequestersQuery->whereDate('request_date', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $topRequestersQuery->whereDate('request_date', '<=', $this->toDate);
        }
        if (! $this->fromDate && ! $this->toDate) {
            $topRequestersQuery->whereYear('request_date', $this->year);
        }

        $topRequesters = $topRequestersQuery
            ->select('requester_id', DB::raw('COUNT(*) as total'))
            ->groupBy('requester_id')
            ->orderByDesc('total')
            ->with('requester')
            ->limit(10)
            ->get();

        return view('livewire.reports', [
            'totalThisYear' => $totalThisYear,
            'monthly' => $monthly,
            'outstanding' => $outstanding,
            'topRequesters' => $topRequesters,
            'availableYears' => DocumentNumber::query()->selectRaw('DISTINCT year')->orderByDesc('year')->pluck('year'),
        ]);
    }
}
