<?php

namespace App\Livewire;

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
        $yearQuery = DocumentNumber::where('year', $this->year)
            ->whereNotNull('requester_id');

        $totalThisYear = (clone $yearQuery)->count();

        $byMonth = (clone $yearQuery)
            ->selectRaw('MONTH(record_date) as month, COUNT(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $monthly = collect(range(1, 12))->map(fn ($m) => [
            'month' => $m,
            'total' => $byMonth[$m] ?? 0,
        ]);

        $outstanding = DocumentNumber::outstanding()
            ->with('requester')
            ->orderBy('expected_return_date')
            ->get();

        $topRequestersQuery = DocumentNumber::query()->whereNotNull('requester_id');

        if ($this->fromDate) {
            $topRequestersQuery->whereDate('record_date', '>=', $this->fromDate);
        }
        if ($this->toDate) {
            $topRequestersQuery->whereDate('record_date', '<=', $this->toDate);
        }
        if (! $this->fromDate && ! $this->toDate) {
            $topRequestersQuery->where('year', $this->year);
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
