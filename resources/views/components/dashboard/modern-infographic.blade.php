@props([
    'stats' => [],
    'year' => date('Y'),
    'userName' => auth()->user()->name ?? 'Peneliti LPPM'
])

@php
    // Default fallback values if not passed from controller
    $totalUsulan = $stats['total_usulan'] ?? 142;
    $totalDidanai = $stats['total_didanai'] ?? 114;
    $persenDidanai = $totalUsulan > 0 ? round(($totalDidanai / $totalUsulan) * 100) : 80;

    $totalPkm = $stats['total_pkm'] ?? 68;
    $totalPkmDidanai = $stats['total_pkm_didanai'] ?? 34;
    $persenPkm = $totalPkm > 0 ? round(($totalPkmDidanai / $totalPkm) * 100) : 50;

    $persenAnggaran = $stats['persen_anggaran'] ?? 60;
    $totalAnggaran = $stats['total_anggaran'] ?? 'Rp 850.000.000';
    $realisasiAnggaran = $stats['realisasi_anggaran'] ?? 'Rp 510.000.000';

    $barData = $stats['yearly_bars'] ?? [
        ['year' => '2022', 'val' => 45],
        ['year' => '2023', 'val' => 28],
        ['year' => '2024', 'val' => 58],
        ['year' => '2025', 'val' => 78],
        ['year' => '2026', 'val' => 52],
    ];

    $splinePoints = $stats['spline_points'] ?? [
        ['x' => 50, 'y' => 120, 'val' => 32],
        ['x' => 120, 'y' => 150, 'val' => 20],
        ['x' => 170, 'y' => 100, 'val' => 48],
        ['x' => 230, 'y' => 60, 'val' => 75],
        ['x' => 280, 'y' => 130, 'val' => 30],
        ['x' => 350, 'y' => 70, 'val' => 68],
    ];
@endphp

<div class="modern-infographic-container my-3" style="font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;">
    <style>
        .infographic-card {
            background: radial-gradient(circle at 50% 0%, rgba(14, 165, 233, 0.12) 0%, rgba(15, 23, 42, 0.85) 100%);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 1.25rem;
            box-shadow: 0 10px 30px -10px rgba(2, 132, 199, 0.25);
            backdrop-filter: blur(12px);
        }
        .glow-cyan-text {
            color: #38bdf8;
            text-shadow: 0 0 16px rgba(56, 189, 248, 0.6);
        }
        .hex-badge {
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            background: linear-gradient(180deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
        }
        .capsule-body {
            background: linear-gradient(180deg, #00d2ff 0%, #0084ff 50%, #0051ff 100%);
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 12px 24px -6px rgba(0, 132, 255, 0.45), inset 0 2px 6px rgba(255, 255, 255, 0.4);
            transition: all 0.25s ease-in-out;
        }
        .capsule-body:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 30px -4px rgba(0, 132, 255, 0.65), inset 0 2px 8px rgba(255, 255, 255, 0.6);
        }
        .gauge-svg {
            filter: drop-shadow(0px 8px 16px rgba(14, 165, 233, 0.35));
        }
        .bar-capsule {
            transition: height 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
    </style>

    <!-- Welcome / Broadcast Banner LPPM -->
    <div class="card p-3 mb-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0369a1 100%); border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 1rem;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%); box-shadow: 0 0 18px rgba(79, 172, 254, 0.6);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="mb-0 text-white font-weight-bold">
                        Selamat Datang, <span class="glow-cyan-text">{{ $userName }}</span>!
                    </h3>
                    <p class="mb-0 text-white-50 small mt-1">
                        Pemberitahuan LPPM: Periode Monitoring & Evaluasi (Monev) {{ $year }} sedang dibuka. Tetap semangat berkarya dalam Tri Dharma Perguruan Tinggi!
                    </p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-cyan text-dark font-weight-bold px-3 py-2 rounded-pill shadow-sm">
                    Tahun Akademik {{ $year }}
                </span>
            </div>
        </div>
    </div>

    <!-- Main 3D Blue/Cyan Infographic Board -->
    <div class="infographic-card p-4 p-md-5 text-white">
        
        <!-- SECTION 1: 3 Circular Gauges -->
        <div class="row text-center justify-content-center mb-5 gy-4">
            <!-- Gauge 1: Usulan Didanai -->
            <div class="col-12 col-md-4 d-flex flex-column align-items-center">
                <div class="position-relative d-inline-block">
                    <svg class="gauge-svg" width="170" height="170" viewBox="0 0 170 170">
                        <defs>
                            <linearGradient id="g1-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#00f2fe"/>
                                <stop offset="100%" stop-color="#0066fe"/>
                            </linearGradient>
                            <radialGradient id="sphere1" cx="40%" cy="40%" r="65%">
                                <stop offset="0%" stop-color="#38bdf8"/>
                                <stop offset="45%" stop-color="#0284c7"/>
                                <stop offset="90%" stop-color="#034b75"/>
                                <stop offset="100%" stop-color="#022a42"/>
                            </radialGradient>
                            <filter id="glow1" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="4" result="blur" />
                                <feComposite in="SourceGraphic" in2="blur" operator="over" />
                            </filter>
                        </defs>
                        <!-- Orbit Track Ring -->
                        <circle cx="85" cy="85" r="74" fill="none" stroke="rgba(56, 189, 248, 0.15)" stroke-width="4" />
                        <path d="M 85 11 A 74 74 0 1 1 20 120" fill="none" stroke="url(#g1-grad)" stroke-width="6" stroke-linecap="round" filter="url(#glow1)" />
                        <!-- Central Sphere -->
                        <circle cx="85" cy="85" r="54" fill="url(#sphere1)" stroke="#38bdf8" stroke-width="3" style="filter: drop-shadow(0 6px 12px rgba(0,0,0,0.5));" />
                        <circle cx="70" cy="70" r="16" fill="rgba(255,255,255,0.22)" filter="blur(3px)" />
                        <text x="85" y="93" text-anchor="middle" fill="#ffffff" font-size="24" font-weight="800" letter-spacing="-0.5px">{{ $persenDidanai }}%</text>
                    </svg>
                </div>
                <h5 class="mt-3 mb-1 text-white font-weight-bold">Usulan Penelitian Didanai</h5>
                <span class="text-white-50 small">{{ $totalDidanai }} dari {{ $totalUsulan }} Proposal Lolos</span>
            </div>

            <!-- Gauge 2: Target Pengabdian PKM -->
            <div class="col-12 col-md-4 d-flex flex-column align-items-center">
                <div class="position-relative d-inline-block">
                    <svg class="gauge-svg" width="170" height="170" viewBox="0 0 170 170">
                        <defs>
                            <linearGradient id="g2-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#38bdf8"/>
                                <stop offset="100%" stop-color="#2563eb"/>
                            </linearGradient>
                            <radialGradient id="sphere2" cx="40%" cy="40%" r="65%">
                                <stop offset="0%" stop-color="#38bdf8"/>
                                <stop offset="45%" stop-color="#0284c7"/>
                                <stop offset="90%" stop-color="#034b75"/>
                                <stop offset="100%" stop-color="#022a42"/>
                            </radialGradient>
                        </defs>
                        <circle cx="85" cy="85" r="74" fill="none" stroke="rgba(56, 189, 248, 0.15)" stroke-width="4" />
                        <path d="M 85 159 A 74 74 0 0 1 159 85" fill="none" stroke="url(#g2-grad)" stroke-width="6" stroke-linecap="round" filter="url(#glow1)" />
                        <circle cx="85" cy="85" r="54" fill="url(#sphere2)" stroke="#38bdf8" stroke-width="3" style="filter: drop-shadow(0 6px 12px rgba(0,0,0,0.5));" />
                        <circle cx="70" cy="70" r="16" fill="rgba(255,255,255,0.22)" filter="blur(3px)" />
                        <text x="85" y="93" text-anchor="middle" fill="#ffffff" font-size="24" font-weight="800" letter-spacing="-0.5px">{{ $persenPkm }}%</text>
                    </svg>
                </div>
                <h5 class="mt-3 mb-1 text-white font-weight-bold">Pengabdian Masyarakat (PKM)</h5>
                <span class="text-white-50 small">{{ $totalPkmDidanai }} dari {{ $totalPkm }} Mitra Berjalan</span>
            </div>

            <!-- Gauge 3: Realisasi Anggaran -->
            <div class="col-12 col-md-4 d-flex flex-column align-items-center">
                <div class="position-relative d-inline-block">
                    <svg class="gauge-svg" width="170" height="170" viewBox="0 0 170 170">
                        <defs>
                            <linearGradient id="g3-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#00f2fe"/>
                                <stop offset="100%" stop-color="#0284c7"/>
                            </linearGradient>
                            <radialGradient id="sphere3" cx="40%" cy="40%" r="65%">
                                <stop offset="0%" stop-color="#38bdf8"/>
                                <stop offset="45%" stop-color="#0284c7"/>
                                <stop offset="90%" stop-color="#034b75"/>
                                <stop offset="100%" stop-color="#022a42"/>
                            </radialGradient>
                        </defs>
                        <circle cx="85" cy="85" r="74" fill="none" stroke="rgba(56, 189, 248, 0.15)" stroke-width="4" />
                        <path d="M 125 147 A 74 74 0 1 1 159 85" fill="none" stroke="url(#g3-grad)" stroke-width="6" stroke-linecap="round" filter="url(#glow1)" />
                        <circle cx="85" cy="85" r="54" fill="url(#sphere3)" stroke="#38bdf8" stroke-width="3" style="filter: drop-shadow(0 6px 12px rgba(0,0,0,0.5));" />
                        <circle cx="70" cy="70" r="16" fill="rgba(255,255,255,0.22)" filter="blur(3px)" />
                        <text x="85" y="93" text-anchor="middle" fill="#ffffff" font-size="24" font-weight="800" letter-spacing="-0.5px">{{ $persenAnggaran }}%</text>
                    </svg>
                </div>
                <h5 class="mt-3 mb-1 text-white font-weight-bold">Realisasi Anggaran LPPM</h5>
                <span class="text-white-50 small">{{ $realisasiAnggaran }} / {{ $totalAnggaran }}</span>
            </div>
        </div>

        <!-- SECTION 2: 4 Hexagonal Capsule Metric Cards (01, 02, 03, 04) -->
        <div class="row g-4 mb-5 justify-content-center">
            <!-- Capsule 01 -->
            <div class="col-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="hex-badge text-white font-weight-bold d-flex align-items-center justify-content-center mb-0 position-relative" style="width: 68px; height: 60px; z-index: 2;">
                        <span style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px;">01</span>
                    </div>
                    <div class="capsule-body text-white w-100 p-3 pt-4" style="margin-top: -16px; min-height: 200px;">
                        <div class="text-uppercase small font-weight-bold tracking-wider" style="letter-spacing: 1.5px; opacity: 0.9;">USULAN</div>
                        <div class="display-6 font-weight-bold my-1">{{ $totalUsulan }}</div>
                        <div class="small text-white-50 px-2" style="font-size: 0.78rem; line-height: 1.3;">
                            Total proposal penelitian yang terdaftar dan lolos seleksi administratif.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capsule 02 -->
            <div class="col-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="hex-badge text-white font-weight-bold d-flex align-items-center justify-content-center mb-0 position-relative" style="width: 68px; height: 60px; z-index: 2;">
                        <span style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px;">02</span>
                    </div>
                    <div class="capsule-body text-white w-100 p-3 pt-4" style="margin-top: -16px; min-height: 200px;">
                        <div class="text-uppercase small font-weight-bold tracking-wider" style="letter-spacing: 1.5px; opacity: 0.9;">DIDANAI</div>
                        <div class="display-6 font-weight-bold my-1">{{ $totalDidanai }}</div>
                        <div class="small text-white-50 px-2" style="font-size: 0.78rem; line-height: 1.3;">
                            Proposal yang dinyatakan lolos reviewer dan menandatangani kontrak penugasan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capsule 03 -->
            <div class="col-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="hex-badge text-white font-weight-bold d-flex align-items-center justify-content-center mb-0 position-relative" style="width: 68px; height: 60px; z-index: 2;">
                        <span style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px;">03</span>
                    </div>
                    <div class="capsule-body text-white w-100 p-3 pt-4" style="margin-top: -16px; min-height: 200px;">
                        <div class="text-uppercase small font-weight-bold tracking-wider" style="letter-spacing: 1.5px; opacity: 0.9;">PKM MITRA</div>
                        <div class="display-6 font-weight-bold my-1">{{ $totalPkm }}</div>
                        <div class="small text-white-50 px-2" style="font-size: 0.78rem; line-height: 1.3;">
                            Aktivitas pengabdian masyarakat bekerjasama dengan UMKM dan instansi.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capsule 04 -->
            <div class="col-6 col-lg-3">
                <div class="d-flex flex-column align-items-center text-center">
                    <div class="hex-badge text-white font-weight-bold d-flex align-items-center justify-content-center mb-0 position-relative" style="width: 68px; height: 60px; z-index: 2;">
                        <span style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.5px;">04</span>
                    </div>
                    <div class="capsule-body text-white w-100 p-3 pt-4" style="margin-top: -16px; min-height: 200px;">
                        <div class="text-uppercase small font-weight-bold tracking-wider" style="letter-spacing: 1.5px; opacity: 0.9;">LUARAN & HKI</div>
                        <div class="display-6 font-weight-bold my-1">{{ $stats['total_luaran'] ?? 48 }}</div>
                        <div class="small text-white-50 px-2" style="font-size: 0.78rem; line-height: 1.3;">
                            Artikel jurnal terakreditasi SINTA/Scopus, Hak Cipta, dan Paten terdaftar.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 3: Charts Row (Capsule Bars & Mountain Spline) -->
        <div class="row g-4 align-items-stretch">
            <!-- Left: Capsule Bar Chart with Bead Rings -->
            <div class="col-12 col-lg-6">
                <div class="p-4 h-100 rounded-3" style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(56, 189, 248, 0.2);">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1 text-white font-weight-bold">Volume Usulan & Pendanaan</h4>
                            <span class="text-white-50 small">Tren kuota tahunan proposal Tri Dharma</span>
                        </div>
                        <span class="badge bg-primary-lt">Per Tahun</span>
                    </div>

                    <div class="d-flex align-items-end justify-content-between px-2 pt-4" style="height: 220px; border-bottom: 2px solid rgba(56, 189, 248, 0.3);">
                        @foreach ($barData as $b)
                            @php
                                $heightPercent = min(100, max(20, $b['val']));
                            @endphp
                            <div class="d-flex flex-column align-items-center gap-2" style="width: 16%;">
                                <div class="position-relative w-100 d-flex flex-column align-items-center" style="height: 180px; justify-content: flex-end;">
                                    <!-- 3D Capsule Bar -->
                                    <div class="bar-capsule w-100 position-relative" style="height: {{ $heightPercent }}%; border-radius: 9999px; background: linear-gradient(180deg, #00f2fe 0%, #0080ff 60%, #0044cc 100%); box-shadow: 0 4px 14px rgba(0, 162, 255, 0.5);">
                                        <!-- Bead Sphere at Top of Capsule -->
                                        <div class="position-absolute" style="top: 3px; left: 50%; transform: translateX(-50%); width: 18px; height: 18px; border-radius: 50%; background: radial-gradient(circle at 35% 35%, #ffffff 0%, #38bdf8 60%, #0369a1 100%); box-shadow: 0 0 10px rgba(255,255,255,0.8), inset 0 0 4px rgba(0,0,0,0.4);">
                                            <div style="width: 6px; height: 6px; background: #fff; border-radius: 50%; margin: 2px 0 0 2px; opacity: 0.8;"></div>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-white font-weight-bold small">{{ $b['year'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right: Mountain Spline Area Chart with Luminous Nodes -->
            <div class="col-12 col-lg-6">
                <div class="p-4 h-100 rounded-3 position-relative" style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(56, 189, 248, 0.2);">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1 text-white font-weight-bold">Grafik Pertumbuhan & Seleksi</h4>
                            <span class="text-white-50 small">Persentase kelolosan pendanaan usulan</span>
                        </div>
                        <span class="badge bg-info-lt">Realtime Analytics</span>
                    </div>

                    <div class="position-relative" style="height: 220px;">
                        <svg viewBox="0 0 400 200" preserveAspectRatio="none" style="width: 100%; height: 100%;">
                            <defs>
                                <linearGradient id="mountainGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                    <stop offset="0%" stop-color="#00e5ff" stop-opacity="0.85"/>
                                    <stop offset="40%" stop-color="#0077ff" stop-opacity="0.6"/>
                                    <stop offset="100%" stop-color="#021c3b" stop-opacity="0.05"/>
                                </linearGradient>
                            </defs>
                            <!-- Grid lines -->
                            <line x1="0" y1="40" x2="400" y2="40" stroke="rgba(56, 189, 248, 0.15)" stroke-width="1" />
                            <line x1="0" y1="90" x2="400" y2="90" stroke="rgba(56, 189, 248, 0.15)" stroke-width="1" />
                            <line x1="0" y1="140" x2="400" y2="140" stroke="rgba(56, 189, 248, 0.15)" stroke-width="1" />
                            <line x1="0" y1="190" x2="400" y2="190" stroke="rgba(56, 189, 248, 0.3)" stroke-width="2" />

                            <!-- Mountain Spline Polygon -->
                            <path d="M 0 190 L 50 120 L 120 150 L 170 100 L 230 60 L 280 130 L 350 70 L 400 110 L 400 190 Z" fill="url(#mountainGrad)" />
                            <path d="M 0 190 L 50 120 L 120 150 L 170 100 L 230 60 L 280 130 L 350 70 L 400 110" fill="none" stroke="#00f2fe" stroke-width="3.5" style="filter: drop-shadow(0 0 8px rgba(0,242,254,0.7));" />

                            <!-- Luminous Glow Nodes -->
                            @foreach ($splinePoints as $p)
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="6" fill="#ffffff" stroke="#0077ff" stroke-width="3" style="filter: drop-shadow(0 0 8px #00f2fe);" />
                            @endforeach
                        </svg>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
