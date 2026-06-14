<div>
    {{-- Modal Ajukan Surat --}}
    <div class="modal modal-blur fade @if($showModal) show @endif" 
         style="display: @if($showModal) block @else none @endif;" 
         tabindex="-1" 
         role="dialog" 
         aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="ti ti-mail-forward me-2"></i> Ajukan Surat Terintegrasi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModal" aria-label="Close"></button>
                </div>
                <form wire:submit.prevent="submit">
                    <div class="modal-body">
                        <div class="alert alert-info border-0 shadow-sm mb-4">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-info-circle fs-2 me-2"></i>
                                <div>
                                    Data judul, tim dosen, dan mahasiswa akan <strong>ditarik otomatis</strong> dari data proposal Anda untuk menjamin kesesuaian borang akreditasi.
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label required">Jenis Surat</label>
                                <select class="form-select @error('letterTypeId') is-invalid @enderror" wire:model.live="letterTypeId">
                                    <option value="">-- Pilih Jenis Surat --</option>
                                    @foreach($availableTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }} ({{ strtoupper($type->code) }})</option>
                                    @endforeach
                                </select>
                                @error('letterTypeId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($selectedType?->code === 'SP')
                            <div class="col-md-12">
                                <label class="form-label required">Tujuan Surat (Nama Pimpinan Mitra)</label>
                                <input type="text" class="form-control @error('destinationName') is-invalid @enderror" 
                                       wire:model="destinationName" placeholder="Contoh: Bapak Kepala Desa Bugangan / Direktur PT. Batik Pesisir">
                                @error('destinationName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label required">Hari, Tanggal Kegiatan</label>
                                <input type="text" class="form-control @error('dateString') is-invalid @enderror" 
                                       wire:model="dateString" placeholder="Contoh: Selasa, 23 Juni 2026">
                                @error('dateString') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label required">Waktu Kegiatan</label>
                                <input type="text" class="form-control @error('timeString') is-invalid @enderror" 
                                       wire:model="timeString" placeholder="Contoh: 08.00 WIB s.d. selesai">
                                @error('timeString') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label required">Tempat / Lokasi</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                       wire:model="location">
                                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Tembusan (Opsional)</label>
                                <textarea class="form-control" rows="2" wire:model="tembusan"></textarea>
                                <small class="text-muted">Gunakan baris baru untuk tembusan lebih dari satu.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-link link-secondary me-auto" wire:click="closeModal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary shadow-sm px-4">
                            <i class="ti ti-send me-2"></i> Kirim ke Kepala LPPM
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div>
