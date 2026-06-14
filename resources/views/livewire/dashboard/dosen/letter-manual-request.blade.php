<div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <form wire:submit.prevent="submit" onsubmit="return false">
                                {{-- Pilih Jenis Surat --}}
                                <div class="mb-4">
                                    <label class="form-label required">Jenis Surat</label>
                                    <select class="form-select" wire:model="letterTypeId" required>
                                        <option value="">-- Pilih Jenis Surat --</option>
                                        @foreach($letterTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->code }} - {{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('letterTypeId')
                                    <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Detail Kegiatan --}}
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

                                {{-- Tim Pelaksana --}}
                                <div class="mb-4">
                                    <label class="form-label">Tim Pelaksana</label>
                                    <div class="input-icon mb-2">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-search"></i>
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
                                                <tr>
                                                    <td>{{ $member['name'] }}</td>
                                                    <td>
                                                        <select class="form-select form-select-sm" wire:model.lazy="team.{{ $index }}.role">
                                                            <option value="Ketua">Ketua</option>
                                                            <option value="Anggota">Anggota</option>
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
                                    @else
                                    <div class="text-muted small">Tim akan ditambahkan otomatis (Anda sebagai Ketua).</div>
                                    @endif
                                </div>

                                {{-- Submit --}}
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('dashboard.dosen.surat.dashboard') }}" class="btn btn-link">Batal</a>
                                    <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                                        <i class="ti ti-send me-1"></i> Kirim ke Kepala LPPM
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
