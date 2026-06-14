<div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                                <input type="text" class="form-control" placeholder="Cari nomor surat atau jenis surat..." wire:model.live="search">
                            </div>
                        </div>
                        <div class="col-auto">
                            <select class="form-select form-select-sm" wire:model="statusFilter">
                                <option value="">Semua Status</option>
                                <option value="pending_approval">Perlu Diproses</option>
                                <option value="published">Diterbitkan</option>
                                <option value="rejected">Ditolak</option>
                                <option value="cancelled">Dibatalkan</option>
                                <option value="ready_to_print">Siap Cetak</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select class="form-select form-select-sm" wire:model="typeFilter">
                                <option value="">Semua Jenis</option>
                                @foreach($letterTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Nomor Surat</th>
                                <th>Jenis</th>
                                <th>Sumber</th>
                                <th>Status</th>
                                <th>Tanggal Ajukan</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($letters as $letter)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $letter->letter_number ?? 'Sedang Diproses' }}</div>
                                </td>
                                <td>
                                    <div>{{ $letter->letterType->name }}</div>
                                    <div class="text-muted small">{{ $letter->letterType->code }}</div>
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
                                    @if($letter->rejection_reason)
                                    <div class="text-danger small mt-1">Alasan: {{ $letter->rejection_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    {{ $letter->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="btn-list flex-nowrap">
                                        @if(in_array($letter->status, ['published', 'ready_to_print']) && $letter->file_path)
                                        <a href="{{ route('letter.view', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm" title="Lihat PDF">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <a href="{{ route('letter.download', $letter->id) }}" target="_blank" class="btn btn-outline-info btn-sm" title="Unduh PDF">
                                            <i class="ti ti-download"></i>
                                        </a>
                                        @elseif(in_array($letter->status, ['published', 'ready_to_print']))
                                        <span class="text-muted small">PDF belum tersedia</span>
                                        @endif

                                        @if($letter->status === 'pending_approval')
                                        <button class="btn btn-outline-danger btn-sm" wire:click="cancel('{{ $letter->id }}')" wire:confirm="Yakin ingin membatalkan surat ini?">
                                            <i class="ti ti-x"></i>
                                        </button>
                                        @endif

                                        @if($letter->status === 'rejected')
                                        <button class="btn btn-outline-primary btn-sm" wire:click="openResubmitModal('{{ $letter->id }}')">
                                            <i class="ti ti-refresh"></i> Ajukan Ulang
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="ti ti-mail-opened icon-lg mb-2"></i>
                                    <div>Belum ada surat yang diajukan.</div>
                                    <a href="{{ route('letters.manual-request') }}" class="btn btn-primary btn-sm mt-2">
                                        <i class="ti ti-plus me-1"></i> Ajukan Surat
                                    </a>
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

    {{-- Resubmit Modal --}}
    <div class="modal modal-blur fade @if($showResubmitModal) show @endif" 
         style="display: @if($showResubmitModal) block @else none @endif;" 
         tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajukan Ulang Surat</h5>
                    <button type="button" class="btn-close" wire:click="$set('showResubmitModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Judul Kegiatan</label>
                        <input type="text" class="form-control" wire:model="resubmitData.title" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Jenis Kegiatan</label>
                            <select class="form-select" wire:model="resubmitData.activityType">
                                <option value="Penelitian">Penelitian</option>
                                <option value="PKM">PKM</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Hari/Tanggal</label>
                            <input type="text" class="form-control" wire:model="resubmitData.dateString" placeholder="Senin, 15 Juni 2026">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Waktu</label>
                            <input type="text" class="form-control" wire:model="resubmitData.timeString" placeholder="08:00 - 12:00 WIB">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Lokasi</label>
                            <input type="text" class="form-control" wire:model="resubmitData.location">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tembusan</label>
                        <textarea class="form-control" wire:model="resubmitData.tembusan" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" wire:click="$set('showResubmitModal', false)">Batal</button>
                    <button type="button" class="btn btn-primary" wire:click="confirmResubmit">
                        <i class="ti ti-send me-1"></i> Kirim Ulang
                    </button>
                </div>
            </div>
        </div>
    </div>
    @if($showResubmitModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
