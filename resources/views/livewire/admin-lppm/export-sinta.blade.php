<div class="row row-cards">
    {{-- Year Filter & SINTA Portal --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="fw-bold mb-1">Filter Tahun Pelaksanaan</div>
                    <div class="text-muted small">Pilih tahun anggaran data yang akan diekspor ke SINTA</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="https://sinta.kemdiktisaintek.go.id/authorverification" target="_blank" class="btn btn-outline-primary btn-sm d-none d-sm-inline-flex" style="font-size: 0.75rem;">
                         Buka Portal SINTA
                    </a>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            
                        </span>
                        <select class="form-select" wire:model.live="selectedYear" style="min-width: 130px;">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                    Tahun {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Export Cards --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid #206bc4 !important; border-top-style: solid !important;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-md bg-primary-lt text-primary me-3">
                        
                    </div>
                    <div>
                        <h3 class="card-title fw-bold mb-0">Penelitian (Research)</h3>
                        <div class="text-muted small">Data penelitian internal LPPM</div>
                    </div>
                    <div class="ms-auto">
                        <div class="h1 fw-bold text-primary mb-0">{{ $summary['research'] }}</div>
                        <div class="text-muted small text-end">proposal</div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted small mb-2 fw-semibold">Kolom yang akan diekspor:</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['SINTA ID Ketua', 'Nama & NIDN', 'Judul', 'Skema', 'Tahun', 'Dana', 'Institusi', 'Member Tim (5)'] as $col)
                            <span class="badge bg-primary-lt text-primary">{{ $col }}</span>
                        @endforeach
                    </div>
                </div>

                @if($summary['research'] > 0)
                    <a href="{{ route('export-sinta.research', ['year' => $selectedYear]) }}"
                        class="btn btn-primary w-100"
                        wire:navigate.disabled
                        id="btn-download-research">
                        
                        Unduh Excel Penelitian ({{ $summary['research'] }} data)
                    </a>
                @else
                    <div class="alert alert-warning mb-0 py-2 small">
                        
                        Tidak ada data penelitian yang Disetujui/Selesai untuk tahun <strong>{{ $selectedYear }}</strong>.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-top: 4px solid #0ca678 !important; border-top-style: solid !important;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-md bg-green-lt text-green me-3">
                        
                    </div>
                    <div>
                        <h3 class="card-title fw-bold mb-0">Pengabdian Masyarakat (PKM)</h3>
                        <div class="text-muted small">Data PKM internal LPPM</div>
                    </div>
                    <div class="ms-auto">
                        <div class="h1 fw-bold text-green mb-0">{{ $summary['pkm'] }}</div>
                        <div class="text-muted small text-end">proposal</div>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted small mb-2 fw-semibold">Kolom yang akan diekspor:</div>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(['SINTA ID Ketua', 'Nama & NIDN', 'Judul', 'Skema', 'Tahun', 'Dana', 'Institusi', 'Member Tim (5)'] as $col)
                            <span class="badge bg-green-lt text-green">{{ $col }}</span>
                        @endforeach
                    </div>
                </div>

                @if($summary['pkm'] > 0)
                    <a href="{{ route('export-sinta.pkm', ['year' => $selectedYear]) }}"
                        class="btn btn-success w-100"
                        wire:navigate.disabled
                        id="btn-download-pkm">
                        
                        Unduh Excel PKM ({{ $summary['pkm'] }} data)
                    </a>
                @else
                    <div class="alert alert-warning mb-0 py-2 small">
                        
                        Tidak ada data PKM yang Disetujui/Selesai untuk tahun <strong>{{ $selectedYear }}</strong>.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Panduan Upload --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h4 class="card-title mb-0 fw-bold">
                    
                    Panduan Upload ke SINTA Author Verification
                </h4>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    @foreach([
                        ['1', 'primary', 'Unduh File Excel', 'Klik tombol unduh di atas sesuai jenis kegiatan (Penelitian atau PKM). File akan ter-download otomatis.'],
                        ['2', 'azure', 'Buka Portal SINTA', 'Akses sinta.kemdiktisaintek.go.id/authorverification menggunakan akun operator institusi ITSNU Pekalongan.'],
                        ['3', 'green', 'Upload File', 'Di halaman Author Verification, pilih menu upload/import data penelitian atau pengabdian. Pilih file Excel yang sudah diunduh.'],
                        ['4', 'orange', 'Verifikasi Data', 'Sistem SINTA akan memvalidasi data. Periksa preview data sebelum konfirmasi submit. Pastikan NIDN dan SINTA ID ketua sudah benar.'],
                    ] as [$num, $color, $title, $desc])
                    <div class="col-md-3">
                        <div class="d-flex">
                            <div class="avatar avatar-sm bg-{{ $color }}-lt text-{{ $color }} me-3 flex-shrink-0 fw-bold">{{ $num }}</div>
                            <div>
                                <div class="fw-semibold small">{{ $title }}</div>
                                <div class="text-muted" style="font-size: 12px;">{{ $desc }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 d-flex align-items-center gap-2">
                
                <span class="text-muted small">
                    Pastikan data profil dosen (terutama <strong>SINTA ID</strong> dan <strong>NIDN</strong>) sudah lengkap di menu 
                    <a href="{{ route('users.index') }}" wire:navigate class="text-primary">Kelola Pengguna</a> 
                    sebelum mengekspor. Data yang kosong akan menyebabkan validasi SINTA gagal.
                </span>
            </div>
        </div>
    </div>
</div>
