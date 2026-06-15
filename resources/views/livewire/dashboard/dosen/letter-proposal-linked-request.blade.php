<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
            <h5 class="card-title mb-0">
                <i class="ti ti-file-plus me-2"></i> Buat Surat dari Proposal
            </h5>
            <a href="{{ route('dashboard.dosen.surat.dashboard') }}" class="btn btn-outline-secondary btn-sm" wire:navigate.hover>
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="ti ti-info-circle fs-2 me-2"></i>
                    <div>
                        Data judul, lokasi, dan tim diisi otomatis dari proposal <strong>{{ $proposal->title }}</strong>.
                        Anda dapat mengedit data tersebut sesuai kebutuhan.
                    </div>
                </div>
            </div>

            <form wire:submit.prevent="submit">
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

                <div class="mb-4">
                    <label class="form-label required">Judul Kegiatan</label>
                    <input type="text" class="form-control" wire:model="title" required>
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
                        <label class="form-label required">Hari/Tanggal Kegiatan</label>
                        <input type="text" class="form-control" wire:model="dateString" placeholder="Contoh: Selasa, 23 Juni 2026" required>
                        @error('dateString')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Waktu Kegiatan</label>
                        <input type="text" class="form-control" wire:model="timeString" placeholder="Contoh: 08.00 WIB s.d. selesai" required>
                        @error('timeString')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Lokasi</label>
                        <input type="text" class="form-control" wire:model="location" required>
                        @error('location')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                @if($selectedLetterType && $selectedLetterType->code === 'SP')
                <div class="mb-4">
                    <label class="form-label required">Tujuan Surat (Nama Pimpinan Mitra)</label>
                    <input type="text" class="form-control" wire:model="destinationName" placeholder="Contoh: Bapak Kepala Desa Bugangan / Direktur PT. Batik Pesisir">
                    @error('destinationName')
                    <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
                @endif

                <div class="mb-4">
                    <label class="form-label">Tembusan</label>
                    <textarea class="form-control" wire:model="tembusan" rows="2"></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Tim Pelaksana <small class="text-muted">(dari proposal, bisa diedit)</small></label>
                    <div class="input-icon mb-2">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" class="form-control" wire:model.live.debounce.500ms="searchQuery" placeholder="Ketik nama dosen untuk menambah anggota...">
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
                                        <select class="form-select form-select-sm" wire:model="team.{{ $index }}.role">
                                            <option value="Ketua">Ketua</option>
                                            <option value="Anggota">Anggota</option>
                                            <option value="Mahasiswa">Mahasiswa</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeTeamMember({{ $index }})">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('dashboard.dosen.surat.dashboard') }}" class="btn btn-link" wire:navigate.hover>Batal</a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ti ti-send me-1"></i> Kirim ke Kepala LPPM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
