<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            {{-- Status Tabs --}}
            <ul class="nav nav-tabs card-header-tabs">
                @php
                    $tabs = [
                        'pending_approval' => ['label' => 'Perlu Diproses', 'icon' => 'ti-clock', 'count' => $stats['pending']],
                        'published' => ['label' => 'Diterbitkan', 'icon' => 'ti-check', 'count' => $stats['published']],
                        'rejected' => ['label' => 'Ditolak', 'icon' => 'ti-x', 'count' => $stats['rejected']],
                        'cancelled' => ['label' => 'Dibatalkan', 'icon' => 'ti-ban', 'count' => $stats['cancelled']],
                        'ready_to_print' => ['label' => 'Siap Cetak', 'icon' => 'ti-printer', 'count' => $stats['ready_to_print']],
                    ];
                @endphp
                @foreach($tabs as $key => $tab)
                <li class="nav-item">
                    <a class="nav-link {{ $statusFilter === $key ? 'active' : '' }}" href="#" wire:click.prevent="$set('statusFilter', '{{ $key }}')">
                        <i class="ti {{ $tab['icon'] }} me-1"></i> {{ $tab['label'] }}
                        <span class="badge bg-secondary-lt ms-1">{{ $tab['count'] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>

            {{-- Search & Batch Actions --}}
            <div class="row align-items-center mt-3">
                <div class="col">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Cari nomor surat, nama dosen, atau jenis surat..." wire:model.live="search">
                    </div>
                </div>
                @if($statusFilter === 'pending_approval')
                <div class="col-auto">
                    <div class="btn-list">
                        @if(count($selectedIds) > 0)
                        <button class="btn btn-success btn-sm" wire:click="batchApprove">
                            <i class="ti ti-check me-1"></i> Setujui ({{ count($selectedIds) }})
                        </button>
                        <button class="btn btn-danger btn-sm" wire:click="batchReject">
                            <i class="ti ti-x me-1"></i> Tolak ({{ count($selectedIds) }})
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        @if($statusFilter === 'pending_approval')
                        <th class="w-1">
                            <input type="checkbox" class="form-check-input" wire:click="toggleSelectAll" {{ count($selectedIds) === $letters->total() && $letters->total() > 0 ? 'checked' : '' }}>
                        </th>
                        @endif
                        <th>Jenis Surat</th>
                        <th>Dosen Pengaju</th>
                        <th>Sumber</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                    <tr>
                        @if($statusFilter === 'pending_approval')
                        <td>
                            <input type="checkbox" class="form-check-input" value="{{ $letter->id }}" wire:click="toggleSelect('{{ $letter->id }}')" {{ in_array($letter->id, $selectedIds) ? 'checked' : '' }}>
                        </td>
                        @endif
                        <td>
                            <div class="fw-bold">{{ $letter->letterType->name }}</div>
                            <div class="text-muted small">{{ $letter->letter_number ?? 'Nomor belum terbit' }}</div>
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
                                @if($letter->status === 'pending_approval')
                                <button class="btn btn-primary btn-sm shadow-sm" wire:click="preview('{{ $letter->id }}')">
                                    <i class="ti ti-signature me-1"></i> Proses
                                </button>
                                @else
                                <a href="{{ route('letter.download', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="ti ti-download me-1"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $statusFilter === 'pending_approval' ? 7 : 6 }}" class="text-center py-5 text-muted">
                            Tidak ada surat dalam kategori ini.
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

    {{-- Preview Modal --}}
    <div class="modal modal-blur fade @if($showPreviewModal) show @endif" 
         style="display: @if($showPreviewModal) block @else none @endif;" 
         tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Preview & Persetujuan Surat</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showPreviewModal', false)"></button>
                </div>
                <div class="modal-body p-0 bg-light" style="height: 70vh; overflow-y: auto;">
                    @if($selectedLetter)
                    <div class="p-5 bg-white shadow-sm mx-auto my-4" style="max-width: 800px; min-height: 1000px; color: #000;">
                        <div class="text-center mb-4">
                            <h3 class="mb-0">{{ strtoupper($selectedLetter->letterType->name) }}</h3>
                            <div class="text-muted">Nomor: [OTOMATIS SAAT APPROVE]</div>
                        </div>
                        <div class="mb-4">
                            <strong>Judul:</strong> {{ $selectedLetter->metadata['title'] ?? '-' }}<br>
                            <strong>Dosen:</strong> {{ $selectedLetter->user->name }}<br>
                            <strong>Waktu:</strong> {{ $selectedLetter->metadata['date_string'] ?? '-' }}, {{ $selectedLetter->metadata['time_string'] ?? '-' }}<br>
                            <strong>Lokasi:</strong> {{ $selectedLetter->metadata['location'] ?? '-' }}
                            @if(!empty($selectedLetter->metadata['destination_name']))
                            <br><strong> Tujuan:</strong> {{ $selectedLetter->metadata['destination_name'] }}
                            @endif
                        </div>
                        <div class="mb-4">
                            <strong>Tim Pelaksana:</strong>
                            <table class="table table-bordered table-sm mt-2">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>NIDN/NIM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedLetter->team_snapshot as $member)
                                    <tr>
                                        <td>{{ $member['name'] }}</td>
                                        <td>{{ $member['role'] }}</td>
                                        <td>{{ $member['identifier'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <hr>
                        <div class="alert alert-info border-0">
                            <strong>Metode Tanda Tangan:</strong> {{ strtoupper($selectedLetter->signature_mode) }}
                            @if($selectedLetter->rejection_reason)
                            <br><strong class="text-danger">Alasan Penolakan Sebelumnya:</strong> {{ $selectedLetter->rejection_reason }}
                            @endif
                        </div>
                        {{-- Log Timeline --}}
                        @if($selectedLetter->logs->count())
                        <div class="mt-4">
                            <strong>Riwayat:</strong>
                            <div class="list-group list-group-flush mt-2">
                                @foreach($selectedLetter->logs->sortByDesc('created_at') as $log)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $log->notes }}</span>
                                        <small class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <small class="text-muted">oleh {{ $log->user->name ?? '-' }}</small>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($selectedLetter && $selectedLetter->status === 'pending_approval')
                    <button type="button" class="btn btn-outline-danger me-auto" wire:click="openRejectModal('{{ $selectedLetter?->id }}')">
                        Tolak Surat
                    </button>
                    @endif
                    <button type="button" class="btn btn-link link-secondary" wire:click="$set('showPreviewModal', false)">Batal</button>
                    @if($selectedLetter && $selectedLetter->status === 'pending_approval')
                    <button type="button" class="btn btn-success px-4 shadow-sm" wire:click="approve('{{ $selectedLetter?->id }}')">
                        <i class="ti ti-check me-2"></i> Tanda Tangani & Publish
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if($showPreviewModal)
    <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Reject Modal --}}
    <div class="modal modal-blur fade @if($showRejectModal) show @endif" 
         style="display: @if($showRejectModal) block @else none @endif;" 
         tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alasan Penolakan</h5>
                    <button type="button" class="btn-close" wire:click="$set('showRejectModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Alasan:</label>
                        <select class="form-select" wire:model="rejectReason">
                            <option value="">-- Pilih Alasan --</option>
                            <option value="Data tim belum lengkap">Data tim belum lengkap</option>
                            <option value="Judul kegiatan tidak sesuai">Judul kegiatan tidak sesuai</option>
                            <option value="Tanggal kegiatan sudah lewat">Tanggal kegiatan sudah lewat</option>
                            <option value="Lokasi tidak terdeteksi">Lokasi tidak terdeteksi</option>
                            <option value="Dokumen lampiran kurang">Dokumen lampiran kurang</option>
                            <option value="Lainnya">Lainnya (isi catatan di bawah)</option>
                        </select>
                    </div>
                    @if($rejectReason === 'Lainnya')
                    <div class="mb-3">
                        <textarea class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..." wire:model="rejectReason"></textarea>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" wire:click="$set('showRejectModal', false)">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="confirmReject" {{ empty($rejectReason) ? 'disabled' : '' }}>
                        <i class="ti ti-x me-1"></i> Tolak Surat
                    </button>
                </div>
            </div>
        </div>
    </div>
    @if($showRejectModal)
    <div class="modal-backdrop fade show" style="z-index: 1040;"></div>
    @endif
</div>
