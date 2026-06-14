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
                                    <span class="text-muted small">Perlu Diproses</span>
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

            <div class="row row-deck row-cards mb-4">
                <div class="col-xl-3 col-sm-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="text-muted small">Dibatalkan</span>
                                    <h2 class="mb-0 mt-1 text-secondary">{{ $stats['cancelled'] }}</h2>
                                </div>
                                <div class="avatar avatar-lg bg-secondary-lt">
                                    <i class="ti ti-ban avatar-icon"></i>
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
                                    <span class="text-muted small">Siap Cetak</span>
                                    <h2 class="mb-0 mt-1 text-info">{{ $stats['ready_to_print'] }}</h2>
                                </div>
                                <div class="avatar avatar-lg bg-info-lt">
                                    <i class="ti ti-printer avatar-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="row row-deck row-cards mb-4">
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="ti ti-file-plus text-primary" style="font-size: 2rem;"></i>
                            <h5 class="mt-3">Buat Surat Baru</h5>
                            <p class="text-muted small mb-3">Ajukan surat manual untuk kebutuhan penelitian atau pengabdian</p>
                            <a href="{{ route('dashboard.dosen.surat.buat') }}" class="btn btn-primary">
                                <i class="ti ti-plus me-2"></i> Buat Surat
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4">
                            <i class="ti ti-clock-history text-info" style="font-size: 2rem;"></i>
                            <h5 class="mt-3">Riwayat Surat</h5>
                            <p class="text-muted small mb-3">Lihat status dan riwayat pengajuan surat Anda</p>
                            <a href="{{ route('dashboard.dosen.surat.riwayat') }}" class="btn btn-info">
                                <i class="ti ti-history me-2"></i> Lihat Riwayat
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Letters --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0"><i class="ti ti-clock me-2"></i> Surat Terbaru Saya</h5>
                    <a href="{{ route('dashboard.dosen.surat.riwayat') }}" class="btn btn-link btn-sm">Lihat Semua</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Nomor Surat</th>
                                <th>Jenis</th>
                                <th>Sumber</th>
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
                                    @if($letter->source === 'manual')
                                        <span class="badge bg-blue-lt px-2 py-1">
                                            <span class="badge bg-blue me-1"></span> Manual
                                        </span>
                                    @else
                                        <span class="badge bg-purple-lt px-2 py-1">
                                            <span class="badge bg-purple me-1"></span> Proposal
                                        </span>
                                    @endif
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
                                <td colspan="6" class="text-center py-5 text-muted">
                                    Belum ada surat. <a href="{{ route('dashboard.dosen.surat.buat') }}" class="link-primary">Ajukan surat sekarang</a>
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