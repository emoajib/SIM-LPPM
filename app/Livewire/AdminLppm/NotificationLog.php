<?php

namespace App\Livewire\AdminLppm;

use App\Models\NotificationLog as NotificationLogModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationLog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $channelFilter = 'all';

    #[Url]
    public string $statusFilter = 'all';

    public function mount(): void
    {
        abort_unless(Auth::user()?->activeHasAnyRole(['admin lppm', 'kepala lppm', 'rektor', 'superadmin']), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = NotificationLogModel::with('user')->latest();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('recipient', 'like', '%'.$this->search.'%')
                    ->orWhere('notification_type', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->channelFilter !== 'all') {
            $query->where('channel', $this->channelFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin-lppm.notification-log', [
            'logs' => $query->paginate(20),
        ]);
    }
}
