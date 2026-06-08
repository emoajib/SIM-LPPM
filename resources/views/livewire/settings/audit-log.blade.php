<div>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="row mb-3 g-2">
        <div class="col-md-4">
            <input type="text" wire:model.live.debounce.500ms="searchUser" class="form-control" placeholder="Cari pengguna…" />
        </div>
        <div class="col-md-3">
            <select wire:model.live="activity" class="form-select">
                <option value="all">Semua Aktivitas</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="login_failed">Gagal Login</option>
                <option value="page_view">Page View</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" wire:model.live="dateFrom" class="form-control" placeholder="Dari tanggal" />
        </div>
        <div class="col-md-2">
            <input type="date" wire:model.live="dateTo" class="form-control" placeholder="Sampai tanggal" />
        </div>
        <div class="col-md-3 mt-2 mt-md-0">
            <input type="text" wire:model.live.debounce.500ms="ipAddress" class="form-control" placeholder="Cari IP…" />
        </div>
    </div>

    <div class="table-responsive">
        <table class="card-table table table-vcenter text-nowrap datatable">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Kegiatan</th>
                    <th>Deskripsi</th>
                    <th>IP</th>
                    <th>URL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            @if($log->user)
                                <a href="{{ route('users.show', $log->user) }}" wire:navigate.hover>{{ $log->user->name }}</a>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td>{{ $log->activity }}</td>
                        <td class="text-wrap" style="max-inline-size:250px">{{ $log->description }}</td>
                        <td>{{ $log->ip_address }}</td>
                        <td class="text-break" style="max-inline-size:200px">{{ $log->url }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Tidak ada aktivitas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-2">
            {{ $logs->links() }}
        </div>
    @endif
</div>
