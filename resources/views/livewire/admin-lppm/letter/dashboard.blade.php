<div>
    <div class="page-body">
        <div class="container-xl">
            {{-- Stats Cards --}}
            <div class="row row-deck row-cards mb-4">
                <div class="col-xl-3 col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small">Total Surat</span>
                                    <h2 class="mb-0 mt-1">{{ $stats['total'] }}</h2>
                                </div>
                                <div class="avatar avatar-lg bg-primary-lt">
                                    <i class="ti ti-mail avatar-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small">Menunggu Persetujuan</span>
                                    <h2 class="mb-0 mt-1 text-warning">{{ $stats['pending'] }}</h2>
                                </div>
                                <div class="avatar avatar-lg bg-warning-lt">
                                    <i class="ti ti-clock avatar-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small">Diterbitkan</span>
                                    <h2 class="mb-0 mt-1 text-success">{{ $stats['published'] }}</h2>
                                </div>
                                <div class="avatar avatar-lg bg-success-lt">
                                    <i class="ti ti-check avatar-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small">Ditolak</span>
                                    <h2 class="mb-0 mt-1 text-danger">{{ $stats['rejected'] }}</h2>
                                </div>
                                <div class="avatar avatar-lg bg-danger-lt">
                                    <i class="ti ti-x avatar-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Letters --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0"><i class="ti ti-clock me-2"></i> Surat Terbaru</h5>
                    <a href="{{ route('admin-lppm.letters.archive') }}" class="btn btn-link btn-sm">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Nomor Surat</th>
                                <th>Jenis</th>
                                <th>Pengaju</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLetters as $letter)
                            <tr>
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
                                    <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }}-lt px-2 py-1">
                                        <span class="badge bg-{{ \App\Models\Letter::statusColor($letter->status) }} me-1"></span> {{ \App\Models\Letter::statusLabel($letter->status) }}
                                    </span>
                                </td>
                                <td class="text-muted">
                                    {{ $letter->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <a href="{{ route('letter.download', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    Belum ada surat.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
