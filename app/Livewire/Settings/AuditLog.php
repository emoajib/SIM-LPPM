<?php

namespace App\Livewire\Settings;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

// Vetted by AI - Manual Review Required by Senior Engineer/Manager
class AuditLog extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public string $searchUser = '';

    public string $activity = 'all';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $ipAddress = '';

    public int $perPage = 10;

    public function updatingSearchUser(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingIpAddress(): void
    {
        $this->resetPage();
    }

    public function updatingActivity(): void
    {
        $this->resetPage();
    }

    /**
     * Ensure only admin LPPM users can view global audit logs.
     */
    public function mount(): void
    {
        abort_unless(Auth::user()?->hasRole('admin lppm'), 403);
    }

    public function render(): View
    {
        $logs = ActivityLog::with('user')
            ->when($this->searchUser !== '', function ($query) {
                $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$this->searchUser}%"));
            })
            ->when($this->activity !== 'all', fn ($query) => $query->where('activity', $this->activity))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->ipAddress !== '', fn ($query) => $query->where('ip_address', 'like', "%{$this->ipAddress}%"))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.settings.audit-log', [
            'logs' => $logs,
        ]);
    }
}
