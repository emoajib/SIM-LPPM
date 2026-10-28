<div class="alert alert-info alert-dismissible fade show border-0 shadow-sm collapse" id="roleInfoAlert" role="alert">
    <div class="d-flex">
        <div>
            
        </div>
        <div>
            @if($role === 'reviewer')
                <h4 class="alert-title">Informasi Reviewer</h4>
                <div class="text-secondary">
                    Sebagai reviewer, Anda dapat melihat seluruh substansi proposal melalui tab-tab di bawah ini. 
                    Gunakan tab <strong>Review & Riwayat</strong> (Tab #5) untuk memberikan evaluasi, catatan, dan rekomendasi Anda.
                </div>
            @elseif($role === 'dosen')
                <h4 class="alert-title">Panduan Pengusul (Dosen)</h4>
                <div class="text-secondary">
                    @if($proposal->detailable_type === 'App\Models\Research')
                        Pastikan Nilai TKT telah diukur dengan benar di <strong>Tab #1 (Identitas Usulan)</strong> bagian 1.5.
                    @else
                        Pastikan Anda telah melengkapi data mitra dan masalah mitra di <strong>Tab #1 (Identitas Usulan)</strong>.
                    @endif
                    Usulan hanya dapat diajukan (Submit) jika semua anggota tim telah menyetujui undangan.
                </div>
            @elseif($role === 'dekan')
                <h4 class="alert-title">Panduan Dekan</h4>
                <div class="text-secondary">
                    Tugas Anda adalah memvalidasi usulan dari fakultas Anda. Periksa kesesuaian skema dan rumpun ilmu. 
                    Berikan keputusan Anda melalui menu persetujuan di <strong>Tab #5</strong>.
                </div>
            @elseif($role === 'admin')
                <h4 class="alert-title">Panduan Admin LPPM</h4>
                <div class="text-secondary">
                    Periksa kelengkapan dokumen substansi dan RAB. 
                    Tugaskan reviewer yang memiliki kepakaran sesuai dengan rumpun ilmu usulan pada <strong>Tab #5</strong>.
                </div>
            @elseif($role === 'kepala')
                <h4 class="alert-title">Panduan Kepala LPPM</h4>
                <div class="text-secondary">
                    Berikan persetujuan awal agar usulan dapat diproses oleh Admin, atau berikan keputusan final berdasarkan 
                    rekomendasi reviewer yang dapat dilihat di <strong>Tab #5</strong>.
                </div>
            @endif
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#roleInfoAlert" aria-label="Close"></button>
</div>
<div class="mb-3">
    <button class="btn btn-ghost-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#roleInfoAlert" aria-expanded="false" aria-controls="roleInfoAlert">
        
        @if($role === 'reviewer') Petunjuk Review @else Bantuan Role @endif
    </button>
</div>
