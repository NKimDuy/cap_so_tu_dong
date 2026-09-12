<?php

namespace App\Livewire;

use App\Enums\SystemLogAction;
use App\Models\SystemLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SystemLogs extends Component
{
    use WithPagination;

    public string $userFilter = '';
    public string $actionFilter = '';
    public string $fromDate = '';
    public string $toDate = '';

    public function updatingUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = SystemLog::with('user')->latest('id');

        if ($this->userFilter) {
            $query->where('user_id', $this->userFilter);
        }

        if ($this->actionFilter) {
            $query->where('action', $this->actionFilter);
        }

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return view('livewire.system-logs', [
            'logs' => $query->paginate(25),
            'users' => User::orderBy('name')->get(),
            'actions' => SystemLogAction::cases(),
        ]);
    }
}
