<div class="row row-cards">
    <div class="col-md-7 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm bg-blue-lt text-blue me-3">
                        
                    </div>
                    <div>
                        <h3 class="card-title fw-bold mb-0">Sinkronisasi Data Dosen via SINTA</h3>
                        <div class="text-muted small">Upload file export dari portal operator SINTA</div>
                    </div>
                </div>
            </div>

            <form wire:submit="import">
                <div class="card-body">

                    {{-- Upload Area --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">
                            
                            File Export SINTA (.xlsx / .xls)
                        </label>
                        <input type="file"
                            wire:model="file"
                            class="form-control form-control-lg @error('file') is-invalid @enderror"
                            accept=".xlsx,.xls">
                        <div class="form-text mt-1">
                            
                            Unduh file dari: SINTA Operator &rarr; <strong>Author Verification</strong> &rarr; <strong>Export Author</strong>
                        </div>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Info Alert --}}
                    <div class="alert alert-info" role="alert">
                        <div class="d-flex">
                            <div class="me-2"></div>
                            <div>
                                <h4 class="alert-title">Cara Kerja Sinkronisasi</h4>
                                <ul class="mb-0 ps-3 text-secondary small">
                                    <li>Sistem mencocokkan data berdasarkan <strong>NIDN</strong> dosen.</li>
                                    <li>Jika dosen <strong>sudah terdaftar</strong> → data profil & skor SINTA diperbarui.</li>
                                    <li>Jika dosen <strong>belum terdaftar</strong> → akun baru dibuat otomatis dengan role <code>dosen</code> dan password default <code>password</code>.</li>
                                    <li>Data yang disinkronisasi: nama, gelar, prodi, jabatan fungsional, skor SINTA (V2/V3), indeks Scopus/GS/WoS/Garuda.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Warning --}}
                    <div class="alert alert-warning" role="alert">
                        <div class="d-flex">
                            <div class="me-2"></div>
                            <div class="small text-secondary">
                                <strong>Perhatian:</strong> Proses ini akan <strong>menimpa</strong> data profil dosen yang sudah ada (nama, gelar, prodi, skor SINTA). Pastikan file yang diunggah adalah export terbaru dari SINTA sebelum melanjutkan.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                    <a href="{{ route('users.index') }}" class="btn btn-link link-secondary">
                         Kembali ke Daftar Pengguna
                    </a>
                    <button type="submit" class="btn btn-primary px-4" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="import">
                             Mulai Sinkronisasi
                        </span>
                        <span wire:loading wire:target="import">
                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                            Memproses data...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-5 col-lg-4">
        {{-- Format Panduan --}}
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-transparent border-0 py-3">
                <h4 class="card-title mb-0 fw-bold">
                    
                    Format Kolom SINTA
                </h4>
            </div>
            <div class="card-body pt-0">
                <p class="text-muted small mb-3">
                    File Excel SINTA harus memiliki kolom-kolom berikut (baris header ke-5):
                </p>
                <div class="d-flex flex-column gap-1">
                    @foreach([
                        ['NIDN', 'Nomor Induk Dosen (wajib)', 'text-red'],
                        ['Nama', 'Nama lengkap dosen', 'text-orange'],
                        ['SINTA ID', 'ID unik SINTA', 'text-muted'],
                        ['Gelar Depan / Belakang', 'Gelar akademik', 'text-muted'],
                        ['Prodi', 'Program Studi', 'text-muted'],
                        ['Jabatan Fungsional', 'Jabatan akademik', 'text-muted'],
                        ['SINTA Score V2/V3', 'Skor publikasi SINTA', 'text-blue'],
                        ['Scopus / GS / WoS', 'Dokumen & sitasi', 'text-blue'],
                    ] as [$col, $desc, $color])
                    <div class="d-flex align-items-start">
                        <code class="me-2 small {{ $color }}" style="min-width: 130px;">{{ $col }}</code>
                        <span class="text-muted small">{{ $desc }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Status Card --}}
        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-4">
                <div class="avatar avatar-lg bg-blue-lt text-blue mb-3 mx-auto">
                    
                </div>
                <div class="text-muted small">
                    Upload file export SINTA dan klik <strong>Mulai Sinkronisasi</strong> untuk memperbarui data dosen secara massal.
                </div>
            </div>
        </div>
    </div>
</div>
