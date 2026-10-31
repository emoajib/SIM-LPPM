<div>
    @if ($dashboardTheme === 'modern')
        {{-- ======================== TEMA MODERN (100% ADAPTASI GOOGLE AI STUDIO) ======================== --}}
        <div class="row row-cards g-3">
            {{-- Antrian Operasional (prioritas untuk admin/superadmin) --}}
            @if (in_array($roleName ?? '', ['admin lppm', 'superadmin']))
            <div class="col-12">
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-rose-lt text-rose p-1">Antrian</span>
                                <span>Antrian Operasional</span>
                            </h3>
                            <div class="text-secondary small mt-0.5">Pekerjaan yang perlu tindakan segera</div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @php
                                $adminQueue = [
                                    ['label' => 'Penugasan Reviewer', 'desc' => 'Tugaskan reviewer ke usulan yang menunggu', 'route' => 'admin-lppm.assign-reviewers', 'icon' => 'user-plus', 'color' => 'primary'],
                                    ['label' => 'Monitoring Laporan', 'desc' => 'Pantau laporan akhir yang perlu tindakan', 'route' => 'admin-lppm.report-approval', 'icon' => 'eye', 'color' => 'azure'],
                                    ['label' => 'Monev', 'desc' => 'Monitoring & evaluasi usulan berjalan', 'route' => 'admin-lppm.monev.index', 'icon' => 'presentation-analytics', 'color' => 'cyan'],
                                    ['label' => 'Beban Kerja Reviewer', 'desc' => 'Pantau beban kerja reviewer', 'route' => 'admin-lppm.reviewer-workload', 'icon' => 'chart-pie', 'color' => 'purple'],
                                    ['label' => 'Monitoring Review', 'desc' => 'Pantau proses review berjalan', 'route' => 'admin-lppm.review-monitoring', 'icon' => 'eye', 'color' => 'teal'],
                                    ['label' => 'Log Notifikasi', 'desc' => 'Riwayat notifikasi sistem', 'route' => 'admin-lppm.notification-log', 'icon' => 'list', 'color' => 'secondary'],
                                ];
                            @endphp
                            @foreach ($adminQueue as $q)
                                @if (Route::has($q['route']))
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <a href="{{ route($q['route']) }}" class="card card-link card-link-pop h-100 border border-{{ $q['color'] }}-lt">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-{{ $q['color'] }}-lt text-{{ $q['color'] }} p-1">
                                                    @include('components.layouts.partials.menu.icon', ['name' => $q['icon'], 'class' => 'icon icon-1'])
                                                </span>
                                                <span class="fw-bold text-dark">{{ $q['label'] }}</span>
                                            </div>
                                            <div class="text-secondary small">{{ $q['desc'] }}</div>
                                        </div>
                                    </a>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            {{-- Antrian Operasional (prioritas untuk kepala lppm) --}}
            @if (($roleName ?? '') === 'kepala lppm')
            <div class="col-12">
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-amber-lt text-amber p-1">Keputusan</span>
                                <span>Antrian Keputusan</span>
                            </h3>
                            <div class="text-secondary small mt-0.5">Proses yang membutuhkan keputusan Anda</div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @php
                                $kepalaQueue = [
                                    ['label' => 'Persetujuan (Awal)', 'desc' => 'Keputusan awal proposal masuk', 'route' => 'kepala-lppm.initial-approval', 'icon' => 'shield-check', 'color' => 'primary'],
                                    ['label' => 'Persetujuan (Akhir)', 'desc' => 'Keputusan akhir proposal', 'route' => 'kepala-lppm.final-decision', 'icon' => 'circle-check', 'color' => 'success'],
                                    ['label' => 'Persetujuan Laporan', 'desc' => 'Persetujuan laporan akhir', 'route' => 'kepala-lppm.report-approval', 'icon' => 'clipboard-check', 'color' => 'azure'],
                                    ['label' => 'Laporan Keuangan', 'desc' => 'Persetujuan laporan keuangan', 'route' => 'kepala-lppm.financial-approval', 'icon' => 'wallet', 'color' => 'green'],
                                    ['label' => 'Capaian Monev', 'desc' => 'Dashboard capaian monev', 'route' => 'kepala-lppm.rektor.monev-dashboard', 'icon' => 'target-arrow', 'color' => 'cyan'],
                                    ['label' => 'Rekap Monev', 'desc' => 'Rekapitulasi monev', 'route' => 'kepala-lppm.monev.recap', 'icon' => 'chart-bar', 'color' => 'purple'],
                                ];
                            @endphp
                            @foreach ($kepalaQueue as $q)
                                @if (Route::has($q['route']))
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <a href="{{ route($q['route']) }}" class="card card-link card-link-pop h-100 border border-{{ $q['color'] }}-lt">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-{{ $q['color'] }}-lt text-{{ $q['color'] }} p-1">
                                                    @include('components.layouts.partials.menu.icon', ['name' => $q['icon'], 'class' => 'icon icon-1'])
                                                </span>
                                                <span class="fw-bold text-dark">{{ $q['label'] }}</span>
                                            </div>
                                            <div class="text-secondary small">{{ $q['desc'] }}</div>
                                        </div>
                                    </a>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            {{-- Tugas & Tindakan (prioritas untuk dosen) --}}
            @if (($roleName ?? '') === 'dosen')
            <div class="col-12">
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-cyan-lt text-cyan p-1">Tugas</span>
                                <span>Tugas & Tindakan Berikutnya</span>
                            </h3>
                            <div class="text-secondary small mt-0.5">Status pengajuan, revisi, dan tenggat Anda</div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @php
                                $dosenTasks = [
                                    ['label' => 'Ajukan Usulan Baru', 'desc' => 'Buat usulan penelitian/pengabdian', 'route' => 'research.proposal.create', 'icon' => 'file-plus', 'color' => 'primary', 'fallback' => 'research/proposal/create'],
                                    ['label' => 'Perbaikan Usulan', 'desc' => 'Revisi usulan yang diminta', 'route' => 'research.proposal-revision.index', 'icon' => 'file-pencil', 'color' => 'azure', 'fallback' => null],
                                    ['label' => 'Laporan Akhir', 'desc' => 'Unggah laporan akhir usulan', 'route' => 'research.final-report.index', 'icon' => 'report', 'color' => 'cyan', 'fallback' => null],
                                    ['label' => 'Realisasi Anggaran', 'desc' => 'Catat realisasi anggaran', 'route' => 'research.daily-note.index', 'icon' => 'wallet', 'color' => 'green', 'fallback' => null],
                                    ['label' => 'Buat Surat', 'desc' => 'Surat tugas/izin', 'route' => 'dashboard.dosen.surat.dashboard', 'icon' => 'file-text', 'color' => 'purple', 'fallback' => 'surat'],
                                    ['label' => 'Usulan Pengabdian', 'desc' => 'Usulan pengabdian masyarakat', 'route' => 'community-service.proposal.index', 'icon' => 'users-group', 'color' => 'teal', 'fallback' => null],
                                ];
                            @endphp
                            @foreach ($dosenTasks as $q)
                                @php
                                    $href = Route::has($q['route']) ? route($q['route']) : (!empty($q['fallback']) ? url($q['fallback']) : null);
                                @endphp
                                @if ($href)
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <a href="{{ $href }}" class="card card-link card-link-pop h-100 border border-{{ $q['color'] }}-lt">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-{{ $q['color'] }}-lt text-{{ $q['color'] }} p-1">
                                                    @include('components.layouts.partials.menu.icon', ['name' => $q['icon'], 'class' => 'icon icon-1'])
                                                </span>
                                                <span class="fw-bold text-dark">{{ $q['label'] }}</span>
                                            </div>
                                            <div class="text-secondary small">{{ $q['desc'] }}</div>
                                        </div>
                                    </a>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            {{-- Tugas Review (prioritas untuk reviewer) --}}
            @if (($roleName ?? '') === 'reviewer')
            <div class="col-12">
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-purple-lt text-purple p-1">Review</span>
                                <span>Tugas Review & Tenggat</span>
                            </h3>
                            <div class="text-secondary small mt-0.5">Prioritas sebelum analisis & grafik</div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @php
                                $reviewerTasks = [
                                    ['label' => 'Review Penelitian', 'desc' => 'Tugas review usulan penelitian', 'route' => 'review.research', 'icon' => 'flask', 'color' => 'primary'],
                                    ['label' => 'Review Pengabdian', 'desc' => 'Tugas review usulan pengabdian', 'route' => 'review.community-service', 'icon' => 'users-group', 'color' => 'azure'],
                                    ['label' => 'Riwayat Review', 'desc' => 'Riwayat tugas review selesai', 'route' => 'review.review-history', 'icon' => 'history', 'color' => 'cyan'],
                                    ['label' => 'Monev Laporan', 'desc' => 'Monev laporan yang ditugaskan', 'route' => 'review.monev', 'icon' => 'presentation-analytics', 'color' => 'green'],
                                ];
                            @endphp
                            @foreach ($reviewerTasks as $q)
                                @if (Route::has($q['route']))
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <a href="{{ route($q['route']) }}" class="card card-link card-link-pop h-100 border border-{{ $q['color'] }}-lt">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-{{ $q['color'] }}-lt text-{{ $q['color'] }} p-1">
                                                    @include('components.layouts.partials.menu.icon', ['name' => $q['icon'], 'class' => 'icon icon-1'])
                                                </span>
                                                <span class="fw-bold text-dark">{{ $q['label'] }}</span>
                                            </div>
                                            <div class="text-secondary small">{{ $q['desc'] }}</div>
                                        </div>
                                    </a>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
            {{-- Capaian & Isu Lintas Unit (prioritas untuk rektor/dekan/kaprodi) --}}
            @if (in_array($roleName ?? '', ['rektor', 'dekan', 'kaprodi']))
            <div class="col-12">
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h3 class="card-title fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-indigo-lt text-indigo p-1">Capaian</span>
                                <span>Capaian & Isu Lintas Unit</span>
                            </h3>
                            <div class="text-secondary small mt-0.5">Pemantauan terhadap target dan akses ke detail</div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            @php
                                $capaianTasks = [
                                    ['label' => 'Monitoring Laporan', 'desc' => 'Pantau laporan lintas unit', 'route' => 'reports.monitoring', 'icon' => 'eye', 'color' => 'primary'],
                                    ['label' => 'Laporan IKU', 'desc' => 'Capaian indikator kinerja utama', 'route' => 'reports.iku', 'icon' => 'award', 'color' => 'azure'],
                                    ['label' => 'Laporan Penelitian', 'desc' => 'Rekap penelitian unit', 'route' => 'reports.research', 'icon' => 'flask', 'color' => 'cyan'],
                                    ['label' => 'Laporan PKM', 'desc' => 'Rekap pengabdian unit', 'route' => 'reports.pkm', 'icon' => 'users-group', 'color' => 'green'],
                                    ['label' => 'Laporan Monev', 'desc' => 'Monitoring & evaluasi', 'route' => 'reports.monev', 'icon' => 'presentation-analytics', 'color' => 'purple'],
                                ];
                                if (($roleName ?? '') === 'dekan') {
                                    $capaianTasks[] = ['label' => 'Persetujuan Proposal', 'desc' => 'Persetujuan proposal fakultas', 'route' => 'dekan.proposals.index', 'icon' => 'shield-check', 'color' => 'teal'];
                                    $capaianTasks[] = ['label' => 'Persetujuan Laporan', 'desc' => 'Persetujuan laporan fakultas', 'route' => 'dekan.reports.index', 'icon' => 'clipboard-check', 'color' => 'amber'];
                                }
                                if (($roleName ?? '') === 'kaprodi') {
                                    $capaianTasks[] = ['label' => 'Monitoring Laporan', 'desc' => 'Monitoring laporan prodi', 'route' => 'kaprodi.report-approval', 'icon' => 'eye', 'color' => 'teal'];
                                    $capaianTasks[] = ['label' => 'Persetujuan Proposal', 'desc' => 'Persetujuan proposal prodi', 'route' => 'kaprodi.proposals.index', 'icon' => 'shield-check', 'color' => 'amber'];
                                }
                                if (($roleName ?? '') === 'rektor') {
                                    $capaianTasks[] = ['label' => 'Capaian Monev', 'desc' => 'Dashboard capaian monev', 'route' => 'kepala-lppm.rektor.monev-dashboard', 'icon' => 'target-arrow', 'color' => 'teal'];
                                }
                            @endphp
                            @foreach ($capaianTasks as $q)
                                @if (Route::has($q['route']))
                                <div class="col-12 col-sm-6 col-lg-4">
                                    <a href="{{ route($q['route']) }}" class="card card-link card-link-pop h-100 border border-{{ $q['color'] }}-lt">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-{{ $q['color'] }}-lt text-{{ $q['color'] }} p-1">
                                                    @include('components.layouts.partials.menu.icon', ['name' => $q['icon'], 'class' => 'icon icon-1'])
                                                </span>
                                                <span class="fw-bold text-dark">{{ $q['label'] }}</span>
                                            </div>
                                            <div class="text-secondary small">{{ $q['desc'] }}</div>
                                        </div>
                                    </a>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
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

                @if (in_array($roleName ?? '', ['dosen']))
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
                @endif

                {{-- Quick Actions (disesuaikan dengan role aktif) --}}
                <div class="card border border-secondary-subtle shadow-sm rounded-3">
                    <div class="card-body p-3">
                        <div class="text-secondary small fw-bold text-uppercase mb-2">Aksi Cepat</div>
                        <div class="d-grid gap-2">
                            @php
                                $quickActions = match ($roleName ?? '') {
                                    'dosen' => [
                                        ['label' => '+ Ajukan Usulan Baru', 'route' => 'research.proposal.create', 'class' => 'btn-primary fw-bold', 'fallback' => 'research/proposal/create'],
                                        ['label' => 'Buat Surat Tugas / Izin', 'route' => 'dashboard.dosen.surat.dashboard', 'class' => 'btn-outline-secondary', 'fallback' => 'surat'],
                                        ['label' => 'Laporan Akhir Saya', 'route' => 'research.final-report.index', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    'reviewer' => [
                                        ['label' => 'Tugas Review Penelitian', 'route' => 'review.research', 'class' => 'btn-primary fw-bold', 'fallback' => null],
                                        ['label' => 'Tugas Review Pengabdian', 'route' => 'review.community-service', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                        ['label' => 'Monev Laporan', 'route' => 'review.monev', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    'admin lppm', 'superadmin' => [
                                        ['label' => 'Penugasan Reviewer', 'route' => 'admin-lppm.assign-reviewers', 'class' => 'btn-primary fw-bold', 'fallback' => null],
                                        ['label' => 'Monitoring Laporan', 'route' => 'admin-lppm.report-approval', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                        ['label' => 'Monev', 'route' => 'admin-lppm.monev.index', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    'kepala lppm' => [
                                        ['label' => 'Persetujuan (Awal)', 'route' => 'kepala-lppm.initial-approval', 'class' => 'btn-primary fw-bold', 'fallback' => null],
                                        ['label' => 'Persetujuan (Akhir)', 'route' => 'kepala-lppm.final-decision', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                        ['label' => 'Persetujuan Laporan', 'route' => 'kepala-lppm.report-approval', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    'dekan' => [
                                        ['label' => 'Persetujuan Proposal', 'route' => 'dekan.proposals.index', 'class' => 'btn-primary fw-bold', 'fallback' => null],
                                        ['label' => 'Persetujuan Laporan', 'route' => 'dekan.reports.index', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    'kaprodi' => [
                                        ['label' => 'Monitoring Laporan', 'route' => 'kaprodi.report-approval', 'class' => 'btn-primary fw-bold', 'fallback' => null],
                                        ['label' => 'Persetujuan Proposal', 'route' => 'kaprodi.proposals.index', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    'rektor' => [
                                        ['label' => 'Monitoring Laporan', 'route' => 'reports.monitoring', 'class' => 'btn-primary fw-bold', 'fallback' => null],
                                        ['label' => 'Laporan IKU', 'route' => 'reports.iku', 'class' => 'btn-outline-secondary', 'fallback' => null],
                                    ],
                                    default => [],
                                };
                            @endphp
                            @foreach ($quickActions as $qa)
                                @php
                                    $href = null;
                                    if (Route::has($qa['route'])) {
                                        $href = route($qa['route']);
                                    } elseif (!empty($qa['fallback'])) {
                                        $href = url($qa['fallback']);
                                    }
                                @endphp
                                @if ($href)
                                    <a href="{{ $href }}" class="btn {{ $qa['class'] }} btn-sm">
                                        {{ $qa['label'] }}
                                    </a>
                                @endif
                            @endforeach
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
