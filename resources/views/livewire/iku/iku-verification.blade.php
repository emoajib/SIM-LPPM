<div>
    <x-slot:header>
        <div class="row align-items-center">
            <div class="col">
                <h2 class="h4 font-weight-bold mb-0 text-primary"><i class="ti ti-check me-1" aria-hidden="true"></i>
Pusat Verifikasi IKU</h2>
                <p class="text-muted mb-0">Validasi luaran penelitian & PKM untuk perhitungan IKU PT</p>
            </div>
            <div class="col-auto">
                <div class="btn-list">
                    <a href="{{ route('accreditation.hub') }}" class="btn btn-outline-primary d-flex align-items-center gap-2" wire:navigate>
                        
                        Dashboard IKU
                    </a>
                </div>
            </div>
        </div>
    </x-slot:header>

    <div class="row g-4">
        <!-- Filters Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    
                                </span>
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                                    placeholder="Cari judul jurnal, artikel, atau usulan...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select wire:model.live="type" class="form-select">
                                <option value="all">Semua Jenis Luaran</option>
                                <option value="publication">Publikasi (Jurnal/Prosiding)</option>
                                <option value="hki">HKI (Paten/Hak Cipta)</option>
                                <option value="product">Produk/Teknologi</option>
                                <option value="pakar">Rekognisi Pakar / Kebijakan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select wire:model.live="status" class="form-select">
                                <option value="all"><i class="ti ti-check me-1" aria-hidden="true"></i>
Semua Status Verifikasi</option>
                                <option value="unverified">Belum Diverifikasi</option>
                                <option value="verified">Sudah Terverifikasi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="col-12">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="w-1 px-4">#</th>
                                <th>Luaran & Usulan</th>
                                <th>Penulis / Pelaksana</th>
                                <th>Peringkat SINTA/Scopus</th>
                                <th>Status</th>
                                <th class="w-1 text-end px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outputs as $output)
                                <tr>
                                    <td class="px-4 text-muted small">
                                        {{ ($outputs->currentPage() - 1) * $outputs->perPage() + $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="font-weight-bold text-dark mb-1">
                                                {{ $output->display_title ?? 'Tanpa Judul' }}
                                            </span>
                                            <span class="small text-muted font-italic">
                                                @if($output->model_type === 'policy')
                                                    Instansi: {{ $output->organization }}
                                                @else
                                                    Usulan: {{ $output->progressReport->proposal->title ?? '-' }}
                                                @endif
                                            </span>
                                            <div class="mt-1">
                                                <span class="badge bg-soft-primary text-primary text-uppercase px-2"
                                                    style="font-size: 0.75rem;">
                                                    @if($output->model_type === 'policy')
                                                        {{ $output->level }}
                                                    @else
                                                        {{ $output->proposalOutput->category ?? 'Unknown' }}
                                                    @endif
                                                </span>
                                            </div>
                                            @if($output->document_url)
                                                <div class="mt-2">
                                                    <a href="{{ $output->document_url }}" target="_blank"
                                                        class="btn btn-sm btn-ghost-info py-1 px-2 border-info-subtle"
                                                        style="font-size: 0.75rem; border: 1px solid rgba(0,0,0,0.1)">
                                                        
                                                        Lihat Dokumen
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs rounded-circle bg-primary-lt me-2 text-uppercase"
                                                style="width: 28px; height: 28px; font-size: 10px;">
                                                {{ substr($output->submitter_name ?? '?', 0, 2) }}
                                            </div>
                                            <div class="small">
                                                <div class="font-weight-bold">
                                                    {{ $output->submitter_name ?? 'Unknown' }}
                                                </div>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    @if($output->model_type === 'policy')
                                                        {{ $output->user->identity->studyProgram->name ?? '' }}
                                                    @else
                                                        {{ $output->progressReport->proposal->submitter->identity->studyProgram->name ?? '' }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($output->model_type !== 'policy')
                                            <select class="form-select form-select-sm border-0 bg-light"
                                                wire:change="updateRank('{{ $output->id }}', '{{ $output->model_type }}', $event.target.value)"
                                                style="max-width: 120px;">
                                                <option value="">- Pilih Rank -</option>
                                                <optgroup label="SINTA">
                                                    @foreach(['S1', 'S2', 'S3', 'S4', 'S5', 'S6'] as $r)
                                                        <option value="{{ $r }}" @if($output->rank == $r) selected @endif>{{ $r }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                                <optgroup label="SCOPUS">
                                                    @foreach(['Q1', 'Q2', 'Q3', 'Q4'] as $r)
                                                        <option value="{{ $r }}" @if($output->rank == $r) selected @endif>{{ $r }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            </select>
                                        @else
                                            <span class="text-muted small">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($output->is_verified_status)
                                            <span class="badge bg-success-lt px-3 py-1">
                                                
                                                Terverifikasi
                                            </span>
                                            <div class="mt-1 small text-muted" style="font-size: 10px;">
                                                Oleh: {{ \App\Models\User::find($output->
verified_by)?->name ?? 'System' }}
                                            </div>
                                        @else
                                            <span class="badge bg-warning-lt px-3 py-1">
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 text-end">
                                        @if($output->model_type !== 'policy')
                                            <button type="button" class="btn btn-sm btn-ghost-info me-1 d-flex align-items-center gap-2"
                                                onclick="openEvidenceModal('{{ $output->id }}', '{{ $output->model_type }}', '{{ str_contains($output->progressReport?->proposal?->detailable_type ?? '', 'CommunityService') ? 'community-service' : 'research' }}')">
                                                Upload Bukti
                                            </button>
                                        @endif
                                        @if($output->is_verified_status)
                                            <button wire:click="unverify('{{ $output->id }}', '{{ $output->model_type }}')"
                                                class="btn btn-sm btn-ghost-danger">
                                                Batalkan
                                            </button>
                                        @else
                                            <button wire:click="verify('{{ $output->id }}', '{{ $output->model_type }}')"
                                                class="btn btn-sm btn-primary px-3 shadow-sm" style="border-radius: 8px;">
                                                <i class="ti ti-check me-1" aria-hidden="true"></i>
                                                Verifikasi
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted">
                                            
                                            Tidak ada luaran yang butuh verifikasi.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($outputs->hasPages())
                    <div class="card-footer border-0 bg-white">
                        {{ $outputs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Upload Bukti Modal (klasik theme parity dengan modern) -->
    <div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Bukti Luaran</h5>
                    <button type="button" class="btn-close d-flex align-items-center gap-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ev-id">
                    <input type="hidden" id="ev-type">
                    <input type="hidden" id="ev-module">
                    <div class="mb-3">
                        <label class="form-label">URL Dokumen (opsional)</label>
                        <input type="url" id="ev-url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File (PDF/Gambar, maks 5MB)</label>
                        <input type="file" id="ev-file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                    <div id="ev-msg" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary d-flex align-items-center gap-2" data-bs-dismiss="modal"><i class="ti ti-x me-1" aria-hidden="true"></i>
Batal</button>
                    <button type="button" class="btn btn-primary d-flex align-items-center gap-2" onclick="submitEvidence()"><i class="ti ti-upload me-1" aria-hidden="true"></i>
Unggah</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openEvidenceModal(id, type, module) {
            document.getElementById('ev-id').value = id;
            document.getElementById('ev-type').value = type;
            document.getElementById('ev-module').value = module;
            document.getElementById('ev-url').value = '';
            document.getElementById('ev-file').value = '';
            document.getElementById('ev-msg').innerText = '';
            var m = new bootstrap.Modal(document.getElementById('evidenceModal'));
            m.show();
        }
        async function submitEvidence() {
            const id = document.getElementById('ev-id').value;
            const type = document.getElementById('ev-type').value;
            const module = document.getElementById('ev-module').value;
            const url = document.getElementById('ev-url').value;
            const file = document.getElementById('ev-file').files[0];
            const msg = document.getElementById('ev-msg');
            const fd = new FormData();
            fd.append('model_type', type);
            if (url) fd.append('url', url);
            if (file) fd.append('file', file);
            try {
                const res = await fetch(`/${module}/api/v1/verifikasi-luaran/${id}/evidence`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Gagal mengunggah bukti');
                msg.className = 'small text-success';
                msg.innerText = data.message || 'Bukti berhasil diunggah.';
                setTimeout(() => location.reload(), 800);
            } catch (e) {
                msg.className = 'small text-danger';
                msg.innerText = e.message;
            }
        }
    </script>

    <style>
        .font-weight-bold {
            font-weight: 700;
        }

        .bg-soft-primary {
            background: rgba(37, 99, 235, 0.1);
        }

        .bg-light {
            background-color: #f8fafc !important;
        }

        .icon {
            vertical-align: middle;
        }
    </style>
</div>