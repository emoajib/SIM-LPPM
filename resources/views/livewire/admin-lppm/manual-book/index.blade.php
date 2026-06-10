<div>
    <div class="d-flex gap-2 mb-3">
        <input type="text" class="form-control" style="max-width:300px" placeholder="Cari manual book..." wire:model.live.debounce.300ms="search">
        <select class="form-select" style="max-width:200px" wire:model.live="statusFilter">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
    </div>

    <div class="alert alert-info d-flex align-items-center mb-3 py-2" role="alert">
        <i class="icon icon-tabler icon-tabler-info-circle me-2"></i>
        <span>Pengelolaan manual book dilakukan di <a href="{{ route('settings.manual-books') }}" wire:navigate class="fw-medium">Pengaturan &rarr; Manual Book</a>.</span>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Role</th>
                        <th>Versi</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Dibuat Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($manualBooks as $book)
                        <tr>
                            <td class="fw-medium">{{ $book->title }}</td>
                            <td>
                                @foreach($book->assigned_roles as $role)
                                    <span class="badge bg-secondary me-1">{{ format_role_name($role) }}</span>
                                @endforeach
                            </td>
                            <td>v{{ $book->version_number }}</td>
                            <td>
                                @if($book->getFirstMedia('manual_book_file'))
                                    <span class="text-success">
                                        <i class="icon icon-tabler icon-tabler-file-check"></i> Terupload
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="icon icon-tabler icon-tabler-file-off"></i> Tidak ada
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($book->status === 'active')
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>{{ $book->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="icon icon-tabler icon-tabler-book-off icon-lg mb-2"></i>
                                <p class="mb-0">Belum ada manual book.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($manualBooks->hasPages())
            <div class="card-footer">
                {{ $manualBooks->links() }}
            </div>
        @endif
    </div>
</div>
