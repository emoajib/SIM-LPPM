<div>
    <x-slot:title>Log Notifikasi</x-slot:title>
    <x-slot:pageTitle>Log Notifikasi</x-slot:pageTitle>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Cari penerima/tipe..." wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="channelFilter">
                        <option value="all">Semua Channel</option>
                        <option value="mail">Email</option>
                        <option value="whatsapp">WhatsApp</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-select" wire:model.live="statusFilter">
                        <option value="all">Semua Status</option>
                        <option value="sent">Terkirim</option>
                        <option value="failed">Gagal</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Penerima</th>
                        <th>Tipe</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Percobaan</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->recipient }}</td>
                            <td>{{ $log->notification_type }}</td>
                            <td>{{ $log->channel }}</td>
                            <td>
                                @if($log->status === 'sent')
                                    <span class="badge bg-success">Terkirim</span>
                                @elseif($log->status === 'failed')
                                    <span class="badge bg-danger">Gagal</span>
                                @else
                                    <span class="badge bg-warning">Pending</span>
                                @endif
                            </td>
                            <td>{{ $log->attempts }}</td>
                            <td class="text-muted small">{{ $log->error_message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Belum ada log notifikasi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $logs->links() }}
        </div>
    </div>
</div>
