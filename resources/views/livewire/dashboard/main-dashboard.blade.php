<div>
    @if ($dashboardTheme === 'modern')
        {{-- ======================== TEMA MODERN (100% ADAPTASI GOOGLE AI STUDIO) ======================== --}}
        <div class="row row-cards g-3">
            {{-- Left / Main Column: Infographic & Proposal Table --}}
            <div class="col-12 col-lg-8 col-xl-9">
                {{-- 1. Modern Infographic Component --}}
                <div class="mb-3">
                    <x-dashboard.modern-infographic :stats="$infographicStats" :userName="$user->name ?? 'Pengguna'" :trendData="$trendData ?? []" />
                </div>

                {{-- 2. Daftar Usulan Terdaftar Table (Modern Presentation) --}}
                <div class="card border border-secondary-subtle shadow-sm rounded-3 overflow-hidden">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-cyan-lt text-cyan p-1">Daftar</span>
                                <span>Daftar Usulan Terdaftar</span>
                            </h3>
                            <div class="text-secondary small mt-0.5">Usulan penelitian dan pengabdian masyarakat terdata di sistem</div>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="badge bg-blue-lt">Tahun Berjalan {{ date('Y') }}</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover card-table">
                            <thead>
                                <tr class="bg-light text-secondary text-uppercase" style="font-size: 11px;">
                                    <th class="w-1">No</th>
                                    <th>Pengusul & Judul Usulan</th>
                                    <th>Skema / Kategori</th>
                                    <th>Tahun</th>
                                    <th>Peran</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentProposals as $index => $prop)
                                    @php
                                        $title = $prop->title ?? 'Judul Usulan Penelitian';
                                        $submitterName = $prop->submitter?->name ?? $user->name ?? '-';
                                        $nidn = $prop->submitter?->identity?->nidn ?? $user->identity?->nidn ?? '-';
                                        $cat = $prop->category_label ?? ($prop->detailable_type === 'App\Models\CommunityService' ? 'Pengabdian Masyarakat' : 'Penelitian Terapan');
                                        $focus = $prop->focus_label ?? ($prop->focusArea?->name ?? 'Sosial Humaniora');
                                        $year = $prop->year ?? ($prop->created_at ? $prop->created_at->format('Y') : date('Y'));
                                        $role = $prop->role ?? 'Ketua';
                                        $statusStr = $prop->status_label ?? ($prop->status?->label() ?? 'Didanai');
                                        $statusClass = $prop->status_class ?? (str_contains(strtolower($statusStr), 'didanai') || str_contains(strtolower($statusStr), 'approved') ? 'bg-success-lt text-success' : 'bg-azure-lt text-azure');
                                    @endphp
                                    <tr>
                                        <td class="text-secondary">{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 420px;" title="{{ $title }}">
                                                {{ $title }}
                                            </div>
                                            <div class="text-secondary small mt-0.5">
                                                <span>{{ $submitterName }}</span>
                                                <span class="text-muted">({{ $nidn }})</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge bg-blue-lt">{{ $cat }}</div>
                                            <div class="text-muted small mt-0.5">{{ $focus }}</div>
                                        </td>
                                        <td class="font-monospace text-secondary">{{ $year }}</td>
                                        <td>
                                            <span class="badge bg-purple-lt">{{ $role }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $statusClass }} fw-bold px-2 py-1">
                                                {{ $statusStr }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada data usulan yang terdaftar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right Column: Profil Dosen & Riwayat Tri Dharma (Matching Google AI Studio Sidebar) --}}
            <div class="col-12 col-lg-4 col-xl-3">
                {{-- Profil Dosen Card --}}
                <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-3">
                    <div class="card-body text-center p-3">
                        <div class="mb-2">
                            <x-user-avatar :user="$user" size="avatar-xl" class="shadow-sm" />
                        </div>
                        <h4 class="card-title fw-bold text-dark mb-0">{{ $user->name ?? 'Pengguna' }}</h4>
                        <div class="text-secondary small">NIDN: {{ $user->identity?->nidn ?? '-' }}</div>
                        <div class="badge bg-green-lt mt-1">{{ format_role_name($roleName) }}</div>

                        <div class="text-muted small mt-2 pt-2 border-top border-secondary-subtle text-start">
                            <div class="d-flex justify-content-between py-1">
                                <span>Pendidikan:</span>
                                <strong class="text-dark">{{ $user->identity?->last_education ?? '-' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Jabatan:</span>
                                <strong class="text-dark">{{ $user->identity?->functional_position ?? '-' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span>Instansi:</span>
                                <strong class="text-dark">{{ $user->identity?->institution_name ?? ($user->identity?->institution?->name ?? 'ITSNU Pekalongan') }}</strong>
                            </div>
                        </div>

                        {{-- SINTA Score Box --}}
                        <div class="mt-3 p-2 bg-light rounded-2 border border-secondary-subtle text-start">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-secondary small fw-bold">Skor SINTA Overall</span>
                                <span class="badge bg-primary text-white font-monospace fw-bold">
                                    {{ number_format($user->identity?->sinta_score_v3_overall ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top border-secondary-subtle small text-muted">
                                <span>Scopus: <strong>{{ $user->identity?->scopus_h_index ?? '-' }}</strong></span>
                                <span>Scholar: <strong>{{ $user->identity?->gs_h_index ?? '-' }}</strong></span>
                                <span>WoS: <strong>{{ $user->identity?->wos_h_index ?? '-' }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Riwayat Tri Dharma Terakhir Card --}}
                <div class="card border border-secondary-subtle shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white py-2.5">
                        <h4 class="card-title fw-bold text-dark mb-0 small text-uppercase">
                            Riwayat Tri Dharma Terakhir
                        </h4>
                    </div>
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item py-3 px-3 text-center text-muted small">
                            Belum ada riwayat Tri Dharma yang dapat ditampilkan.
                        </div>
                        <!--
                        <div class="list-group-item py-2 px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="badge bg-rose-lt text-rose">Penelitian</span>
                                <span class="text-muted small">2026</span>
                            </div>
                            <div class="text-dark fw-bold mt-1 text-truncate" title="Penerapan Sistem ERP UMKM Berkah Jaya">
                                Penerapan Sistem ERP UMKM Berkah Jaya
                            </div>
                        </div>
                        <div class="list-group-item py-2 px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="badge bg-cyan-lt text-cyan">Pengabdian</span>
                                <span class="text-muted small">2026</span>
                            </div>
                            <div class="text-dark fw-bold mt-1 text-truncate" title="Pemberdayaan Pengelolaan BUMDes Digital Marketing">
                                Pemberdayaan Pengelolaan BUMDes Digital Marketing
                            </div>
                        </div>
                        <div class="list-group-item py-2 px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="badge bg-yellow-lt text-yellow">Surat</span>
                                <span class="text-muted small">2026</span>
                            </div>
                            <div class="text-dark fw-bold mt-1 text-truncate" title="Surat Tugas Penelitian Mandiri No: 124/LPPM/ITSNU/2026">
                                Surat Tugas Penelitian Mandiri No: 124/2026
                            </div>
                        </div>
                        <div class="list-group-item py-2 px-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="badge bg-green-lt text-green">Prototipe</span>
                                <span class="text-muted small">2026</span>
                            </div>
                            <div class="text-dark fw-bold mt-1 text-truncate" title="Prototipe Alat Pengering Pewarna Batik Alami">
                                Prototipe Alat Pengering Pewarna Batik Alami
                            </div>
                        </div>
                    </div>
                        -->
                </div>

                {{-- Quick Actions --}}
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <div class="text-secondary small fw-bold text-uppercase mb-2">Aksi Cepat Peneliti</div>
                        <div class="d-grid gap-2">
                            <a href="{{ Route::has('research.proposal.create') ? route('research.proposal.create') : url('research/proposal/create') }}" class="btn btn-primary btn-sm fw-bold">
                                + Ajukan Usulan Baru
                            </a>
                            <a href="{{ Route::has('letters.manual-request') ? route('letters.manual-request') : (Route::has('dashboard.dosen.surat.dashboard') ? route('dashboard.dosen.surat.dashboard') : url('surat')) }}" class="btn btn-outline-secondary btn-sm">
                                Buat Surat Tugas / Izin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ======================== TEMA KLASIK (STANDAR TABLER LPPM) ======================== --}}
        <div class="dashboard-content-area">
            @livewire($dashboardComponent, key($dashboardComponent))
        </div>
    @endif
</div>
