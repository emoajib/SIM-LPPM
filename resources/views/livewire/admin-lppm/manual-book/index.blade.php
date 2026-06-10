<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <input type="text" class="form-control" placeholder="Cari manual book..." wire:model.live.debounce.300ms="search">
            <select class="form-select" wire:model.live="statusFilter">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </div>
        <a href="{{ route('admin-lppm.manual-books.create') }}" class="btn btn-primary" wire:navigate>
            <i class="icon icon-tabler icon-tabler-plus me-1"></i> Tambah Manual Book
        </a>
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
                        <th>Aksi</th>
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
                                <button class="btn btn-sm {{ $book->status === 'active' ? 'btn-success' : 'btn-secondary' }}"
                                    wire:click="toggleStatus('{{ $book->id }}')"
                                    wire:confirm="Ubah status manual book ini?">
                                    {{ $book->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td>{{ $book->creator?->name ?? '-' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin-lppm.manual-books.edit', $book) }}" class="btn btn-outline-primary" wire:navigate>
                                        <i class="icon icon-tabler icon-tabler-edit"></i>
                                    </a>
                                    <button class="btn btn-outline-danger"
                                        wire:click="delete('{{ $book->id }}')"
                                        wire:confirm="Yakin ingin menghapus manual book ini?">
                                        <i class="icon icon-tabler icon-tabler-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
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
