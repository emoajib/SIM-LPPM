@props(['user', 'roleName' => null, 'history' => []])

<div class="space-y-4 w-full">
    {{-- 1. Profil Saya --}}
    <div class="card border border-secondary-subtle shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="small fw-bold">Profil Saya</span>
            <a href="{{ Route::has('settings.profile') ? route('settings.profile') : url('settings/profile') }}" class="text-white opacity-75 small" title="Edit Profil">✏️</a>
        </div>
        <div class="card-body p-3">
            <div class="d-flex gap-3 align-items-center pb-3 border-bottom border-secondary-subtle mb-3">
                <x-user-avatar :user="$user" size="avatar-md" class="shadow-sm" />
                <div class="min-w-0">
                    <h6 class="fw-bold mb-0 text-uppercase small text-truncate">{{ $user->name ?? 'Pengguna' }}</h6>
                    <div class="text-muted" style="font-size:10px; white-space:pre-line;">{{ $user->identity?->nidn ?? '-' }} · {{ $user->identity?->functional_position ?? '-' }}</div>
                    <span class="badge bg-success-lt text-success mt-1" style="font-size:9px;">Aktif</span>
                </div>
            </div>
            <div class="row text-center g-0 border-top border-bottom border-secondary-subtle py-2">
                <div class="col-4 border-end">
                    <div class="fw-bold small">{{ number_format($user->identity?->sinta_score_v3_overall ?? 0, 0, ',', '.') }}</div>
                    <div class="text-muted" style="font-size:9px;">Sinta Score</div>
                </div>
                <div class="col-4 border-end">
                    <div class="fw-bold small">{{ $user->identity?->last_education ?? '-' }}</div>
                    <div class="text-muted" style="font-size:9px;">Pendidikan</div>
                </div>
                <div class="col-4">
                    <div class="fw-bold small text-warning">{{ $user->identity?->functional_position ?? '-' }}</div>
                    <div class="text-muted" style="font-size:9px;">Jabatan</div>
                </div>
            </div>
            <div class="mt-2 pt-2 d-flex justify-content-between align-items-center text-primary small fw-bold" style="font-size:10px;">
                <span>Sinkronisasi Data SINTA Dikti</span>
                <i class="ti ti-chevron-right"></i>
            </div>
        </div>
    </div>

    {{-- 2. Riwayat Usulan --}}
    <div class="card border border-secondary-subtle shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-primary text-white py-2 px-3 d-flex justify-content-between align-items-center">
            <span class="small fw-bold">Riwayat Usulan</span>
            <span class="badge bg-white text-primary" style="font-size:9px;">Real-time</span>
        </div>
        <div class="card-body p-3">
            @forelse ($history as $hist)
                <div class="pb-2 mb-2 border-bottom border-secondary-subtle last:border-0 last:mb-0 last:pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-blue-lt text-blue" style="font-size:9px;">{{ $hist['category'] ?? '-' }}</span>
                    </div>
                    <p class="mb-0 text-muted" style="font-size:10.5px; border-left:2px solid #dee2e6; padding-left:6px;">{{ $hist['title'] ?? '-' }}</p>
                </div>
            @empty
                <div class="text-muted small text-center py-2">Belum ada riwayat usulan.</div>
            @endforelse
        </div>
    </div>

    {{-- 3. Helper Tip --}}
    <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-3">
        <h6 class="small fw-bold text-indigo mb-1">Ekstraksi Template Instan</h6>
        <p class="mb-0 text-indigo" style="font-size:10px;">Semua konfigurasi warna, navigasi, dan tata letak table yang diubah secara visual akan langsung tersinkronisasi ke dalam menu ekspor kode.</p>
    </div>
</div>
