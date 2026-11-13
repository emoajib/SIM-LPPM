{{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
<div>
    <style>
        .btn-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 0;
        }
        .section-form { display: none; }
        .section-form.active { display: block; }
        .section-table { display: block; }
        .section-table.hidden { display: none; }
        .fab-hidden { display: none !important; }
        /* Mitigasi: tabs scroll horizontal di mobile */
        .letter-tabs-scroll { min-width: 0; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .letter-tabs-scroll::-webkit-scrollbar { display: none; }
        .letter-tabs-scroll .nav-tabs { flex-wrap: nowrap !important; min-width: max-content; border-bottom: none; margin-bottom: 0; }
        .letter-tabs-scroll .nav-link { white-space: nowrap; padding: 0.5rem 0.85rem; }
        /* cegah tabs mendorong lebar card */
        .letter-card-header { overflow: hidden; }
        /* search row spacing */
        .letter-search-row { margin-top: 1rem; }
        /* stabilkan kolom tabel kunci */
        .letter-table th:nth-child(1) { width: 48px; }
        .letter-table th:nth-child(5) { width: 110px; }
        .letter-table th:nth-child(6) { width: 120px; }
        /* FAB mobile */
        @media (max-width: 576px) {
            .btn-fab { bottom: 1rem; right: 1rem; width: 48px; height: 48px; }
        }
        /* Mitigasi: sticky header tabel */
        .letter-table thead th { position: sticky; top: 0; z-index: 10; background: #fff; box-shadow: 0 1px 0 #e2e8f0; }
        /* Mitigasi: ruang agar FAB tidak menutupi paginasi */
        .letter-table-wrap { padding-bottom: 5rem; }
        /* Mitigasi: empty state */
        .letter-empty { padding: 3rem 1rem; text-align: center; color: #64748b; }
        .letter-empty svg { width: 48px; height: 48px; margin-bottom: 0.75rem; opacity: 0.5; }
    </style>
    {{-- Tabs + Table --}}
    <div class="card border-0 shadow-sm section-table @if($showForm) hidden @endif" id="sectionTable">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 letter-card-header">
            <div class="d-flex justify-content-end mb-2">
                <span class="badge {{ (app(\App\Models\Setting::class)::get('surat_signature_mode','tte') === 'tte') ? 'bg-info-lt' : 'bg-secondary-lt' }}">
                    Mode tanda tangan: {{ (app(\App\Models\Setting::class)::get('surat_signature_mode','tte') === 'tte') ? 'TTE (QR)' : 'Basah' }}
                </span>
            </div>
            <div class="letter-tabs-scroll">
            <ul class="nav nav-tabs card-header-tabs">
                @php
                    $tabs = [
                        'pending_approval' => ['label' => 'Perlu Diproses', 'count' => $stats['pending']],
                        'published' => ['label' => 'Diterbitkan', 'count' => $stats['published']],
                        'rejected' => ['label' => 'Ditolak', 'count' => $stats['rejected']],
                        'cancelled' => ['label' => 'Dibatalkan', 'count' => $stats['cancelled']],
                        'ready_to_print' => ['label' => 'Siap Cetak', 'count' => $stats['ready_to_print']],
                    ];
                @endphp
                @foreach($tabs as $key => $tab)
                <li class="nav-item" wire:key="tab-{{ $key }}">
                    <a class="nav-link {{ $statusFilter === $key ? 'active' : '' }}" href="#" wire:click.prevent="setFilter('{{ $key }}')">
                         {{ $tab['label'] }}
                        <span class="badge bg-secondary-lt ms-1">{{ $tab['count'] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
            </div>

            <div class="row align-items-center g-2 letter-search-row">
                <div class="col" style="min-width: 0;">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            
                        </span>
                        <input type="text" class="form-control" placeholder="Cari nomor surat atau jenis surat..." wire:model.live="search">
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary btn-sm d-flex align-items-center gap-2" wire:click="showSectionForm">
                        <i class="ti ti-plus me-1" aria-hidden="true"></i>
                        Ajukan Surat
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive mt-3 letter-table-wrap">
            <table class="table table-vcenter card-table table-hover letter-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor Surat</th>
                        <th>Jenis</th>
                        <th>Sumber</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($letters as $letter)
                    <tr wire:key="letter-row-{{ $letter->id }}">
                        <td class="text-muted">{{ $letters->firstItem() + $loop->index }}</td>
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
                                @if(in_array($letter->status?->value, ['published', 'ready_to_print']) && $letter->file_path)
                                <a href="{{ route('letter.view', $letter->
id) }}" target="_blank" class="btn btn-outline-info btn-sm d-flex align-items-center gap-2" title="Lihat PDF">
                                    <i class="ti ti-file me-1" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('letter.download', $letter->
id) }}" target="_blank" class="btn btn-outline-info btn-sm d-flex align-items-center gap-2" title="Unduh PDF">
                                    <i class="ti ti-download me-1" aria-hidden="true"></i>
                                </a>
                                @elseif(in_array($letter->status?->value, ['published', 'ready_to_print']))
                                <span class="text-muted small">PDF belum tersedia</span>
                                @endif

                                @if($letter->status?->value === 'pending_approval')
                                <button class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2" wire:click="cancel('{{ $letter->id }}')" wire:confirm="Yakin ingin membatalkan surat ini?">
                                    <i class="ti ti-trash me-1" aria-hidden="true"></i>
                                </button>
                                @endif

                                @if($letter->status?->value === 'rejected')
                                <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2" wire:click="openResubmitModal('{{ $letter->id }}')">
                                    <i class="ti ti-rotate me-1" aria-hidden="true"></i>
                                    <i class="ti ti-rotate me-1" aria-hidden="true"></i>
                                    Ajukan Ulang
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="letter-empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <div class="fw-semibold mb-1">Belum ada surat dengan status ini.</div>
                            <div class="small mb-3"><i class="ti ti-plus me-1" aria-hidden="true"></i>
Ajukan surat baru untuk memulai.</div>
                            <button class="btn btn-primary btn-sm d-flex align-items-center gap-2" wire:click="showSectionForm">
                                <i class="ti ti-plus me-1" aria-hidden="true"></i>
                                Ajukan Surat Baru
                            </button>
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

    {{-- FAB --}}
    @if($letters->count())
    <button class="btn btn-primary btn-fab shadow-lg d-flex align-items-center gap-2 @if($showForm) fab-hidden @endif d-flex align-items-center gap-2" id="fabAjukan" title="Ajukan Surat Baru" wire:click="showSectionForm"><i class="ti ti-plus me-1" aria-hidden="true"></i>

        
    </button>
    @endif

    {{-- Form --}}
    <div class="card border-0 shadow-sm section-form @if($showForm) active @endif" id="sectionForm">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0"> <i class="ti ti-plus me-1" aria-hidden="true"></i>
 Ajukan Surat Baru</h5>
            <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2" wire:click="showSectionTable">
                 <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                 Kembali
            </button>
        </div>
        <div class="card-body p-4">
            <form wire:submit.prevent="submitLetter" onsubmit="return false">
                <div class="mb-4">
                    <label class="form-label required">Jenis Surat</label>
                    <select class="form-select" wire:model.live="letterTypeId" required>
                        <option value="">-- Pilih Jenis Surat --</option>
                        @foreach($letterTypes as $type)
                        <option value="{{ $type->id }}" wire:key="type-{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                        @endforeach
                    </select>
                    @error('letterTypeId')
                    <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                @if(count($proposals) > 0)
                <div class="mb-4">
                    <label class="form-label">Gunakan Data Proposal</label>
                    <select class="form-select" wire:model.live="selectedProposalId">
                        <option value="">-- Input manual atau pilih proposal --</option>
                        @foreach($proposals as $proposal)
                        <option value="{{ $proposal->id }}" wire:key="prop-{{ $proposal->id }}">
                            {{ $proposal->title }}
                        </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Judul, lokasi, dan tim akan terisi otomatis jika memilih proposal.</small>
                </div>
                @endif

                <div class="mb-4">
                    <label class="form-label required">Judul Kegiatan</label>
                    <input type="text" class="form-control" wire:model="title" placeholder="Judul kegiatan..." required>
                    @error('title')
                    <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Jenis Kegiatan</label>
                        <select class="form-select" wire:model="activityType" required>
                            <option value="Penelitian">Penelitian</option>
                            <option value="PKM">PKM</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Hari/Tanggal</label>
                        <input type="date" class="form-control" wire:model="date" required>
                        @error('date')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label required">Waktu Mulai</label>
                        <input type="time" class="form-control" wire:model="timeStart" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Waktu Selesai</label>
                        <input type="time" class="form-control" wire:model="timeEnd" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Lokasi</label>
                        <input type="text" class="form-control" wire:model="location" placeholder="Contoh: Desa Kedungwuni" required>
                    </div>
                </div>

                @if($selectedLetterType && $selectedLetterType->code === 'SP')
                <div class="mb-4">
                    <label class="form-label required">Tujuan Surat</label>
                    <input type="text" class="form-control" wire:model="destinationName" placeholder="Nama instansi/organisasi tujuan" required>
                    @error('destinationName')
                    <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                @endif

                <div class="mb-4">
                    <label class="form-label">Tembusan</label>
                    <textarea class="form-control" wire:model="tembusan" rows="2" placeholder="1. Arsip&#10;2. ..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Tim Pelaksana</label>
                    <div class="input-icon mb-2">
                        <span class="input-icon-addon">
                            
                        </span>
                        <input type="text" class="form-control" wire:model.live.debounce.500ms="searchQuery" wire:keydown.enter.prevent wire:keydown.enter.stop placeholder="Ketik nama dosen untuk mencari...">
                    </div>

                    @if(count($searchResults) > 0)
                    <div class="list-group mb-2" style="max-height: 200px; overflow-y: auto;">
                        @foreach($searchResults as $dosen)
                        <button type="button" class="list-group-item list-group-item-action" wire:key="search-result-{{ $dosen['id'] }}" wire:click="addTeamMember('{{ $dosen['id'] }}')">
                            <div class="fw-bold">{{ $dosen['name'] }}</div>
                            <small class="text-muted">{{ $dosen['email'] }}</small>
                        </button>
                        @endforeach
                    </div>
                    @endif

                    @if(count($team) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($team as $index => $member)
                                <tr wire:key="team-member-{{ $index }}">
                                    <td>{{ $member['name'] }}</td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model.lazy="team.{{ $index }}.role">
                                            <option value="Ketua">Ketua</option>
                                            <option value="Anggota">Anggota</option>
                                            <option value="Mahasiswa">Mahasiswa</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-2" wire:click="removeTeamMember({{ $index }})" title="Tutup" aria-label="Tutup"><i class="ti ti-trash me-1" aria-hidden="true"></i>
                                            
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-muted small">Tim akan ditambahkan otomatis (Anda sebagai Ketua).</div>
                    @endif
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-link d-flex align-items-center gap-2" onclick="showSectionTable()"><i class="ti ti-x me-1" aria-hidden="true"></i>
Batal</button>
                    <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2" wire:loading.attr="disabled">
                         Kirim ke Kepala LPPM
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Resubmit Modal --}}
    <div class="modal modal-blur fade @if($showResubmitModal) show @endif"
         style="display: @if($showResubmitModal) block @else none @endif;"
         tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-plus me-1" aria-hidden="true"></i>
Ajukan Ulang Surat</h5>
                    <button type="button" class="btn-close d-flex align-items-center gap-2" wire:click="closeResubmitModal"></button>
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
                    <button type="button" class="btn btn-link d-flex align-items-center gap-2" wire:click="closeResubmitModal"><i class="ti ti-x me-1" aria-hidden="true"></i>
Batal</button>
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-2" wire:click="confirmResubmit">
                         Kirim Ulang
                    </button>
                </div>
            </div>
        </div>
    </div>
    @if($showResubmitModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>