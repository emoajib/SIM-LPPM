<div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <div class="row align-items-end g-3">
                        <div class="col-md-4">
                            <label class="form-label">Pencarian</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Nomor, nama dosen, atau jenis surat..." wire:model.live="search">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model="statusFilter">
                                <option value="">Semua</option>
                                <option value="pending_approval">Menunggu Persetujuan</option>
                                <option value="published">Diterbitkan</option>
                                <option value="rejected">Ditolak</option>
                                <option value="cancelled">Dibatalkan</option>
                                <option value="ready_to_print">Siap Cetak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Jenis Surat</label>
                            <select class="form-select" wire:model="typeFilter">
                                <option value="">Semua</option>
                                @foreach($letterTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" wire:model="dateFrom">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" wire:model="dateTo">
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor Surat</th>
                                <th>Jenis</th>
                                <th>Pengaju</th>
                                <th>Sumber</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($letters as $letter)
                            <tr>
                                <td class="text-muted">{{ $letters->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-bold">{{ $letter->letter_number ?? 'Sedang Diproses' }}</div>
                                </td>
                                <td>
                                    <div>{{ $letter->letterType->name }}</div>
                                    <div class="text-muted small">{{ $letter->letterType->code }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2 bg-primary-lt">{{ $letter->user->initials() }}</span>
                                        <div>
                                            <div>{{ $letter->user->name }}</div>
                                            <div class="text-muted small">{{ $letter->user->identity->identity_id ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $letter->source === 'manual' ? 'info' : 'secondary' }}-lt">
                                        {{ $letter->source === 'manual' ? 'Manual' : 'Proposal' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }}-lt px-2 py-1">
                                        <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }} me-1"></span> {{ \App\Models\Letter::statusLabel($letter->status) }}
                                    </span>
                                </td>
                                <td class="text-muted">
                                    {{ $letter->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        @if($letter->file_path)
                                        <a href="{{ route('letter.view', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm" title="Lihat PDF">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        @endif
                                        <a href="{{ route('letter.download', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm" title="Unduh PDF">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    Tidak ada surat yang sesuai filter.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent border-0 pb-4">
                    {{ $letters->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
