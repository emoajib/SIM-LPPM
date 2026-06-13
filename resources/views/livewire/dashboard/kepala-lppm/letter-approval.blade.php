<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-4 pb-0">
            <div class="row align-items-center">
                <div class="col">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Cari nomor surat atau nama dosen..." wire:model.live="search">
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th>Jenis Surat</th>
                        <th>Dosen Pengaju</th>
                        <th>Status</th>
                        <th>Tanggal Terbit</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $letter->letterType->name }}</div>
                            <div class="text-muted small">{{ $letter->letter_number ?? 'Nomor belum terbit' }}</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm me-2 bg-primary-lt">{{ $letter->user->initials() }}</span>
                                <div>
                                    <div>{{ $letter->user->name }}</div>
                                    <div class="text-muted small">{{ $letter->user->identity->identity_id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @php
                                $color = match($letter->status) {
                                    'draft' => 'secondary',
                                    'pending_approval' => 'warning',
                                    'published' => 'success',
                                    'ready_to_print' => 'info',
                                    'rejected' => 'danger',
                                    default => 'secondary'
                                };
                                $label = match($letter->status) {
                                    'draft' => 'Draft',
                                    'pending_approval' => 'Menunggu Tanda Tangan',
                                    'published' => 'Telah Terbit (TTE)',
                                    'ready_to_print' => 'Siap Cetak (Basah)',
                                    'rejected' => 'Ditolak',
                                    default => $letter->status
                                };
                            @endphp
                            <span class="badge bg-{{ $color }}-lt px-2 py-1">
                                <span class="badge bg-{{ $color }} me-1"></span> {{ $label }}
                            </span>
                        </td>
                        <td class="text-muted">
                            {{ $letter->published_at ? $letter->published_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td>
                            <div class="btn-list flex-nowrap">
                                @if($letter->status === 'pending_approval')
                                <button class="btn btn-primary btn-sm shadow-sm" wire:click="preview('{{ $letter->id }}')">
                                    <i class="ti ti-signature me-1"></i> Proses
                                </button>
                                @else
                                <a href="{{ Storage::url($letter->file_path) }}" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="ti ti-download me-1"></i> Download
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            Tidak ada surat yang masuk.
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
                        {{-- Simulating PDF view for preview --}}
                        <div class="text-center mb-4">
                            <h3 class="mb-0">{{ strtoupper($selectedLetter->letterType->name) }}</h3>
                            <div class="text-muted">Nomor: [OTOMATIS SAAT APPROVE]</div>
                        </div>
                        <div class="mb-4">
                            <strong>Judul:</strong> {{ $selectedLetter->metadata['title'] }}<br>
                            <strong>Dosen:</strong> {{ $selectedLetter->user->name }}<br>
                            <strong>Waktu:</strong> {{ $selectedLetter->metadata['date_string'] }}, {{ $selectedLetter->metadata['time_string'] }}<br>
                            <strong>Lokasi:</strong> {{ $selectedLetter->metadata['location'] }}
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
                        <div class="alert alert-warning border-0">
                            <strong>Metode Tanda Tangan:</strong> {{ strtoupper($selectedLetter->signature_mode) }}
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-danger me-auto" onclick="const reason = prompt('Alasan penolakan:'); if(reason) @this.call('reject', '{{ $selectedLetter?->id }}', reason)">
                        Tolak Surat
                    </button>
                    <button type="button" class="btn btn-link link-secondary" wire:click="$set('showPreviewModal', false)">Batal</button>
                    <button type="button" class="btn btn-success px-4 shadow-sm" wire:click="approve('{{ $selectedLetter?->id }}')">
                        <i class="ti ti-check me-2"></i> Tanda Tangani & Publish
                    </button>
                </div>
            </div>
        </div>
    </div>
    @if($showPreviewModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
