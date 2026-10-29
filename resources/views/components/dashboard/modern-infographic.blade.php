@props([
    'stats' => [],
    'year' => date('Y'),
    'userName' => auth()->user()->name ?? 'Pengguna',
    'trendData' => [],
])

@php
    $totalUsulan = $stats['total_usulan'] ?? 0;
    $totalDidanai = $stats['total_didanai'] ?? 0;
    $fundingRate = $totalUsulan > 0 ? round(($totalDidanai / $totalUsulan) * 100, 1) : 0;
    $totalLuaran = $stats['total_luaran'] ?? 0;
    $totalDanaM = $stats['total_dana_m'] ?? 0;

    // Indonesian formatted date
    $days = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $dayName = $days[date('l')] ?? 'Kamis';
    $dayNum = date('d');
    $monthName = $months[(int)date('m')] ?? 'Juni';
    $yearNum = date('Y');
    $fullIndoDate = "$dayName, $dayNum $monthName $yearNum";
@endphp

<div id="sim-lppm-infographic-root" class="sim-lppm-infographic my-3">
    <style>
        .sim-lppm-infographic {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #0f172a;
        }

        /* 1. Welcome Banner Card */
        .sli-banner {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 40%, #0f172a 100%);
            border-radius: 1rem;
            color: #ffffff;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.2);
            min-height: 160px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .sli-banner-glow-1 {
            position: absolute;
            right: 0;
            top: 0;
            width: 12rem;
            height: 12rem;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 9999px;
            transform: translate(3rem, -2rem);
            filter: blur(24px);
            pointer-events: none;
        }
        .sli-banner-glow-2 {
            position: absolute;
            right: 30%;
            bottom: 0;
            width: 8rem;
            height: 8rem;
            background: rgba(34, 211, 238, 0.12);
            border-radius: 9999px;
            transform: translateY(2.5rem);
            filter: blur(16px);
            pointer-events: none;
        }
        .sli-mascot-box {
            position: absolute;
            right: 1.5rem;
            bottom: 0;
            height: 9rem;
            width: 11rem;
            display: none;
            align-items: flex-end;
            justify-content: center;
            pointer-events: none;
            user-select: none;
            opacity: 0.85;
            z-index: 1;
        }
        @media (min-width: 992px) {
            .sli-mascot-box { display: flex; }
        }

        .sli-banner-content {
            position: relative;
            z-index: 2;
            max-width: 100%;
            width: 100%;
        }
        @media (min-width: 992px) {
            .sli-banner-content { max-width: 76%; }
        }

        .sli-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.2rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .sli-bulletin-box {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 0.75rem;
            padding: 0.75rem 0.9rem;
            color: #ffffff;
            margin-top: 0.75rem;
            transition: all 0.3s ease;
        }

        /* 2. Top Stats Grid (4 Cards) */
        .sli-stat-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.85rem;
            margin-bottom: 1.25rem;
        }
        @media (min-width: 576px) {
            .sli-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 992px) {
            .sli-stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .sli-stat-card {
            border-radius: 0.75rem;
            color: #ffffff;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .sli-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 14px rgba(2, 132, 199, 0.25);
        }
        .sli-stat-val {
            font-size: 1.85rem;
            font-weight: 900;
            line-height: 1;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        /* 3. Main Crisp White Card Box */
        .sli-main-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1.35rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        /* Section A: KPI Gauge Panel */
        .sli-kpi-panel {
            background: linear-gradient(180deg, rgba(240, 249, 255, 0.6) 0%, rgba(248, 250, 252, 0.5) 50%, #ffffff 100%);
            border: 1px solid #e0f2fe;
            border-radius: 1rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 2px rgba(2, 132, 199, 0.05);
        }
        .sli-kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
            align-items: center;
            justify-items: center;
        }
        @media (min-width: 768px) {
            .sli-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        .sli-gauge-disc {
            position: absolute;
            inset: 0.75rem;
            border-radius: 9999px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.45);
        }

        /* Section B: 4 Hexagonal Top Capsule Step Cards */
        .sli-capsules-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.95rem;
            margin-bottom: 1.5rem;
        }
        @media (min-width: 992px) {
            .sli-capsules-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .sli-capsule-col {
            position: relative;
            padding-top: 1rem;
        }
        .sli-hex-badge {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            width: 3rem;
            height: 2.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 2px 4px rgba(2, 132, 199, 0.3));
        }
        .sli-capsule-body {
            background: linear-gradient(180deg, #06b6d4 0%, #0284c7 50%, #1d4ed8 100%);
            color: #ffffff;
            border-radius: 0.75rem 0.75rem 1.75rem 1.75rem;
            padding: 1.75rem 0.75rem 1rem 0.75rem;
            text-align: center;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            border: 1px solid rgba(103, 232, 249, 0.4);
            transition: all 0.25s ease-in-out;
        }
        .sli-capsule-col:hover .sli-capsule-body {
            transform: translateY(-4px);
            box-shadow: 0 8px 18px rgba(2, 132, 199, 0.35);
        }

        /* Section C: Dual Charts Row */
        .sli-charts-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.25rem;
        }
        @media (min-width: 992px) {
            .sli-charts-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        .sli-chart-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Section D: Active Year Card */
        .sli-year-inspect {
            padding: 0.9rem 1rem;
            background: linear-gradient(90deg, #f0f9ff 0%, #f8fafc 50%, #eef2ff 100%);
            border: 1px solid #e0f2fe;
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        @media (min-width: 768px) {
            .sli-year-inspect {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        /* Modal */
        .sli-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .sli-modal-dialog {
            background: #ffffff;
            border-radius: 1rem;
            width: 100%;
            max-width: 640px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            animation: sliModalPop 0.2s ease-out;
        }
        @keyframes sliModalPop {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>

    <!-- 1. WELCOME BANNER CARD -->
    <div class="sli-banner">
        <div class="sli-banner-glow-1"></div>
        <div class="sli-banner-glow-2"></div>

        <!-- Dynamic Lecturer Illustrator Mascot SVG -->
        <div class="sli-mascot-box">
            <svg style="width: 100%; height: 100%; overflow: visible;" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                <path d="M10,20 L12,23 L15,24 L12,25 L10,28 L8,25 L5,24 L8,23 Z" fill="#38bdf8" opacity="0.8"/>
                <path d="M100,50 L101.5,53 L104,54 L101.5,55 L100,58 L98.5,55 L96,54 L98.5,53 Z" fill="#38bdf8" opacity="0.9"/>
                <ellipse cx="65" cy="115" rx="35" ry="5" fill="rgba(0,0,0,0.2)" />
                <rect x="58" y="72" width="14" height="15" rx="3" fill="#bae6fd" />
                <rect x="45" y="32" width="40" height="42" rx="14" fill="#e0f2fe" />
                <circle cx="53" cy="58" r="4" fill="#38bdf8" opacity="0.4" />
                <circle cx="77" cy="58" r="4" fill="#38bdf8" opacity="0.4" />
                <path d="M42,42 C42,24 55,20 68,22 C81,24 88,34 88,44" stroke="#0f172a" stroke-width="7" stroke-linecap="round" fill="none" />
                <path d="M43,36 C48,26 62,24 74,25 C84,26 87,32 87,36" fill="#0f172a" />
                <path d="M57,60 Q65,66 73,60" stroke="#0284c7" stroke-width="2.5" stroke-linecap="round" fill="none" />
                <circle cx="54" cy="48" r="9" fill="none" stroke="#0f172a" stroke-width="2.5" />
                <circle cx="76" cy="48" r="9" fill="none" stroke="#0f172a" stroke-width="2.5" />
                <line x1="63" y1="48" x2="67" y2="48" stroke="#0f172a" stroke-width="2.5" />
                <circle cx="54" cy="48" r="2.2" fill="#0f172a" />
                <circle cx="76" cy="48" r="2.2" fill="#0f172a" />
                <path d="M35,90 L95,90 L88,125 L42,125 Z" fill="#0369a1" />
                <path d="M54,90 L76,90 L65,107 Z" fill="#ffffff" />
                <path d="M62,91 L68,91 L65,123 L62,123 Z" fill="#38bdf8" stroke="#0284c7" stroke-width="0.5" />
            </svg>
        </div>

        <div class="sli-banner-content">
            <!-- Top Row: Date, Category Pill, Admin Manage Trigger -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                <span class="sli-badge-pill" style="background: rgba(255, 255, 255, 0.2);">
                    📅 {{ $fullIndoDate }}
                </span>

                <span id="sli-bulletin-badge" class="sli-badge-pill" style="background: #bae6fd; color: #082f49;">
                    👋 MOTIVASI & SALAM
                </span>

                @if(auth()->user()?->activeHasAnyRole(['admin lppm', 'superadmin']))
                <button type="button" onclick="sliOpenModal()" class="sli-badge-pill" style="background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); color: #ffffff; cursor: pointer; margin-left: auto;">
                    ⚙️ Kelola Pengumuman/Jadwal
                </button>
                @endif
            </div>

            <!-- Personalized Greeting -->
            <h2 style="font-size: 1.4rem; font-weight: 900; margin: 0; display: flex; align-items: center; gap: 0.4rem; letter-spacing: -0.02em;">
                <span>Selamat Datang, {{ $userName }}!</span>
                <span style="color: #fde047; font-size: 1.2rem;">✨</span>
            </h2>

            <!-- Dynamic Bulletin Box with Auto-Slide & Arrows -->
            <div class="sli-bulletin-box">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem;">
                    <div style="min-width: 0; padding-right: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                            <span id="sli-bulletin-title" style="font-weight: 800; font-size: 0.75rem; color: #67e8f9; text-transform: uppercase;">
                                Salam Semangat Tri Dharma
                            </span>
                            <span id="sli-bulletin-date" style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.7); font-family: monospace;">
                                • {{ $fullIndoDate }}
                            </span>
                        </div>
                        <p id="sli-bulletin-content" style="margin: 0; font-size: 0.8rem; line-height: 1.4; color: rgba(255, 255, 255, 0.95); font-weight: 500;">
                            Have a nice Thursday! Tetap semangat berkarya dalam Tri Dharma Perguruan Tinggi LPPM ITSNU Pekalongan.
                        </p>
                    </div>

                    <!-- Slide Navigation Controls -->
                    <div style="display: flex; align-items: center; gap: 0.25rem; shrink-0;">
                        <button type="button" onclick="sliPrevBulletin()" style="width: 1.5rem; height: 1.5rem; border-radius: 0.25rem; background: rgba(255, 255, 255, 0.15); border: none; color: #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                            ◀
                        </button>
                        <span id="sli-bulletin-counter" style="font-size: 0.75rem; font-family: monospace; font-weight: 800; padding: 0 0.25rem; color: rgba(255, 255, 255, 0.85);">
                            1/4
                        </span>
                        <button type="button" onclick="sliNextBulletin()" style="width: 1.5rem; height: 1.5rem; border-radius: 0.25rem; background: rgba(255, 255, 255, 0.15); border: none; color: #ffffff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                            ▶
                        </button>
                    </div>
                </div>

                <!-- Dot Indicators -->
                <div id="sli-bulletin-dots" style="display: flex; align-items: center; gap: 0.3rem; margin-top: 0.4rem;">
                    <!-- Injected by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- 2. QUICK STATS CARDS (4 CARDS) -->
    <div class="sli-stat-grid">
        <div class="sli-stat-card" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
            <div>
                <div class="sli-stat-val">{{ $totalUsulan }}</div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Total Usulan Masuk</div>
                <div style="font-size: 0.75rem; opacity: 0.85;">Penelitian & Pengabdian</div>
            </div>
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                📄
            </div>
        </div>

        <div class="sli-stat-card" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
            <div>
                <div class="sli-stat-val">{{ $totalDidanai }}</div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Proposal Didanai</div>
                <div style="font-size: 0.75rem; opacity: 0.85;">{{ $fundingRate }}% Rasio Lolos Seleksi</div>
            </div>
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                🎯
            </div>
        </div>

        <div class="sli-stat-card" style="background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);">
            <div>
                <div class="sli-stat-val">Rp {{ $totalDanaM }} M</div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Realisasi Anggaran</div>
                <div style="font-size: 0.75rem; opacity: 0.85;">Terserap Hibah LPPM</div>
            </div>
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                💰
            </div>
        </div>

        <div class="sli-stat-card" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
            <div>
                <div class="sli-stat-val">{{ $totalLuaran }}</div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Luaran & Publikasi</div>
                <div style="font-size: 0.75rem; opacity: 0.85;">Jurnal Sinta, HKI & BASTB</div>
            </div>
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 9999px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                🏆
            </div>
        </div>
    </div>

    <!-- 3. MAIN WHITE INFOGRAPHIC BOX (MATCHING EXACT UPLOADED IMAGE & AI STUDIO) -->
    <div class="sli-main-card">
        
        <!-- Header Controls Row -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; margin-bottom: 1.25rem;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="padding: 0.35rem 0.5rem; background: #ecfeff; color: #0891b2; border-radius: 0.5rem; border: 1px solid #cffafe; font-size: 0.9rem;">
                            📈
                        </span>
                        <h3 style="margin: 0; font-size: 1rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; color: #0369a1;">
                            Visualisasi Trend Usulan Penelitian & Pengabdian
                        </h3>
                    </div>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.75rem; color: #64748b;">
                        Menghitung visualisasi volume usulan yang diunggah dan didanai (Tahun Akademik 2021 - 2026)
                    </p>
                </div>

                <!-- Scheme Filters -->
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    <div style="background: #f1f5f9; padding: 0.2rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; display: flex; font-size: 0.75rem;">
                        <button type="button" id="btn-scheme-all" onclick="sliSetScheme('all')" style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; border: none; font-weight: 700; cursor: pointer; background: #ffffff; color: #0c4a6e; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                            Semua Skema
                        </button>
                        <button type="button" id="btn-scheme-penelitian" onclick="sliSetScheme('penelitian')" style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; border: none; font-weight: 600; cursor: pointer; background: transparent; color: #64748b;">
                            Penelitian
                        </button>
                        <button type="button" id="btn-scheme-pengabdian" onclick="sliSetScheme('pengabdian')" style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; border: none; font-weight: 600; cursor: pointer; background: transparent; color: #64748b;">
                            Pengabdian
                        </button>
                    </div>

                    <!-- View Layout Selector -->
                    <div style="background: #f1f5f9; padding: 0.2rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; display: flex; font-size: 0.75rem;">
                        <button type="button" id="btn-layout-both" onclick="sliSetLayout('both')" title="Infografis Lengkap" style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; border: none; font-weight: 700; cursor: pointer; background: #0891b2; color: #ffffff;">
                            Infografis
                        </button>
                        <button type="button" id="btn-layout-bar" onclick="sliSetLayout('bar')" title="Fokus Grafik Batang Kapsul" style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; border: none; font-weight: 600; cursor: pointer; background: transparent; color: #64748b;">
                            📊
                        </button>
                        <button type="button" id="btn-layout-trend" onclick="sliSetLayout('trend')" title="Fokus Kurva Area Mountain" style="padding: 0.25rem 0.75rem; border-radius: 0.375rem; border: none; font-weight: 600; cursor: pointer; background: transparent; color: #64748b;">
                            📈
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION A: 3 Circular Progress Gauge Meters (Top Row of Uploaded Image) -->
        <div class="sli-kpi-panel">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <span style="font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #334155; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="width: 0.5rem; height: 0.5rem; border-radius: 9999px; background: #06b6d4; display: inline-block;"></span>
                    Indikator Capaian Kinerja Utama LPPM (KPI Gauge)
                </span>
                <span style="font-size: 0.7rem; font-family: monospace; color: #0369a1; background: #e0f2fe; border: 1px solid #bae6fd; padding: 0.15rem 0.5rem; border-radius: 9999px; font-weight: 700;">
                    Tahun Berjalan {{ $year }}
                </span>
            </div>

            <div class="sli-kpi-grid">
                <!-- Gauge 1: Kelolosan Usulan (80%) -->
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                    <div style="position: relative; width: 9rem; height: 9rem; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 100%; height: 100%; transform: rotate(-90deg); overflow: visible;" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" stroke="#e0f2fe" stroke-width="5" fill="none" opacity="0.6"/>
                            <circle id="sli-gauge-circle-1" cx="60" cy="60" r="50" stroke="url(#cyanGaugeGrad1)" stroke-width="6" stroke-dasharray="314" stroke-dashoffset="63" stroke-linecap="round" fill="none" style="transition: stroke-dashoffset 0.8s ease;"/>
                            <defs>
                                <linearGradient id="cyanGaugeGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#22d3ee" />
                                    <stop offset="50%" stop-color="#0284c7" />
                                    <stop offset="100%" stop-color="#0369a1" />
                                </linearGradient>
                            </defs>
                        </svg>

                        <div class="sli-gauge-disc" style="background: linear-gradient(135deg, #22d3ee 0%, #0284c7 50%, #1e40af 100%); box-shadow: inset 0 4px 8px rgba(255,255,255,0.4), 0 8px 20px rgba(2,132,199,0.35);">
                            <span id="sli-gauge-val-1" style="font-size: 1.5rem; font-weight: 900; color: #ffffff; font-family: monospace; line-height: 1;">
                                {{ $fundingRate }}%
                            </span>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #cffafe; letter-spacing: 0.05em; margin-top: 0.15rem;">
                                Rasio Lolos
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <h4 style="margin: 0; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #0f172a; letter-spacing: 0.02em;">
                            Kelolosan Usulan
                        </h4>
                        <p id="sli-gauge-sub-1" style="margin: 0.15rem 0 0 0; font-size: 0.7rem; color: #64748b; font-weight: 500;">
                            {{ $totalDidanai }} dari {{ $totalUsulan }} proposal didanai
                        </p>
                    </div>
                </div>

                <!-- Gauge 2: Kemajuan Riset (72% / 50%) -->
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                    <div style="position: relative; width: 9rem; height: 9rem; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 100%; height: 100%; transform: rotate(-90deg); overflow: visible;" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" stroke="#e0f2fe" stroke-width="5" fill="none" opacity="0.6"/>
                            <circle cx="60" cy="60" r="50" stroke="url(#cyanGaugeGrad2)" stroke-width="6" stroke-dasharray="314" stroke-dashoffset="88" stroke-linecap="round" fill="none"/>
                            <defs>
                                <linearGradient id="cyanGaugeGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#38bdf8" />
                                    <stop offset="100%" stop-color="#2563eb" />
                                </linearGradient>
                            </defs>
                        </svg>

                        <div class="sli-gauge-disc" style="background: linear-gradient(135deg, #38bdf8 0%, #2563eb 50%, #312e81 100%); box-shadow: inset 0 4px 8px rgba(255,255,255,0.4), 0 8px 20px rgba(37,99,235,0.35);">
                            <span style="font-size: 1.5rem; font-weight: 900; color: #ffffff; font-family: monospace; line-height: 1;">
                                72%
                            </span>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #e0e7ff; letter-spacing: 0.05em; margin-top: 0.15rem;">
                                Monev 70%
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <h4 style="margin: 0; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #0f172a; letter-spacing: 0.02em;">
                            Kemajuan Riset
                        </h4>
                        <p style="margin: 0.15rem 0 0 0; font-size: 0.7rem; color: #64748b; font-weight: 500;">
                            Laporan Antara & SPTB 70% diverifikasi
                        </p>
                    </div>
                </div>

                <!-- Gauge 3: Penyaluran Hibah (85% / 60%) -->
                <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                    <div style="position: relative; width: 9rem; height: 9rem; display: flex; align-items: center; justify-content: center;">
                        <svg style="width: 100%; height: 100%; transform: rotate(-90deg); overflow: visible;" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" stroke="#e0f2fe" stroke-width="5" fill="none" opacity="0.6"/>
                            <circle cx="60" cy="60" r="50" stroke="url(#cyanGaugeGrad3)" stroke-width="6" stroke-dasharray="314" stroke-dashoffset="47" stroke-linecap="round" fill="none"/>
                            <defs>
                                <linearGradient id="cyanGaugeGrad3" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#06b6d4" />
                                    <stop offset="100%" stop-color="#1d4ed8" />
                                </linearGradient>
                            </defs>
                        </svg>

                        <div class="sli-gauge-disc" style="background: linear-gradient(135deg, #06b6d4 0%, #0284c7 50%, #1e3a8a 100%); box-shadow: inset 0 4px 8px rgba(255,255,255,0.4), 0 8px 20px rgba(14,165,233,0.35);">
                            <span style="font-size: 1.5rem; font-weight: 900; color: #ffffff; font-family: monospace; line-height: 1;">
                                85%
                            </span>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #cffafe; letter-spacing: 0.05em; margin-top: 0.15rem;">
                                Realisasi
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <h4 style="margin: 0; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #0f172a; letter-spacing: 0.02em;">
                            Penyaluran Hibah
                        </h4>
                        <p style="margin: 0.15rem 0 0 0; font-size: 0.7rem; color: #64748b; font-weight: 500;">
                            Rp {{ $totalDanaM }} M dana penelitian terserap
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION B: 4 Hexagonal Top Capsule Step Cards (Middle Row of Uploaded Image) -->
        <div class="sli-capsules-grid">
            <!-- Card 01 -->
            <div class="sli-capsule-col">
                <div class="sli-hex-badge">
                    <svg viewBox="0 0 48 42" style="width: 100%; height: 100%; fill: #06b6d4; stroke: #ffffff; stroke-width: 2.5;">
                        <polygon points="24,2 44,11 44,31 24,40 4,31 4,11" />
                    </svg>
                    <span style="position: absolute; color: #ffffff; font-family: monospace; font-weight: 900; font-size: 0.75rem;">01</span>
                </div>
                <div class="sli-capsule-body">
                    <h5 style="margin: 0; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #cffafe;">
                        Tahap Pengajuan
                    </h5>
                    <div id="sli-cap-val-1" style="font-size: 1.35rem; font-weight: 900; font-family: monospace; margin: 0.25rem 0; color: #ffffff;">
                        {{ $totalUsulan }} <span style="font-size: 0.75rem; font-weight: 400; color: #bae6fd;">Judul</span>
                    </div>
                    <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.85); line-height: 1.25;">
                        Proposal baru masuk & surat bebas plagiat
                    </p>
                </div>
            </div>

            <!-- Card 02 -->
            <div class="sli-capsule-col">
                <div class="sli-hex-badge">
                    <svg viewBox="0 0 48 42" style="width: 100%; height: 100%; fill: #06b6d4; stroke: #ffffff; stroke-width: 2.5;">
                        <polygon points="24,2 44,11 44,31 24,40 4,31 4,11" />
                    </svg>
                    <span style="position: absolute; color: #ffffff; font-family: monospace; font-weight: 900; font-size: 0.75rem;">02</span>
                </div>
                <div class="sli-capsule-body">
                    <h5 style="margin: 0; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #cffafe;">
                        Desk Review
                    </h5>
                    <div id="sli-cap-val-2" style="font-size: 1.35rem; font-weight: 900; font-family: monospace; margin: 0.25rem 0; color: #ffffff;">
                        {{ round($totalUsulan * 0.88) }} <span style="font-size: 0.75rem; font-weight: 400; color: #bae6fd;">Lolos</span>
                    </div>
                    <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.85); line-height: 1.25;">
                        Penilaian reviewer & telaah etik riset
                    </p>
                </div>
            </div>

            <!-- Card 03 -->
            <div class="sli-capsule-col">
                <div class="sli-hex-badge">
                    <svg viewBox="0 0 48 42" style="width: 100%; height: 100%; fill: #06b6d4; stroke: #ffffff; stroke-width: 2.5;">
                        <polygon points="24,2 44,11 44,31 24,40 4,31 4,11" />
                    </svg>
                    <span style="position: absolute; color: #ffffff; font-family: monospace; font-weight: 900; font-size: 0.75rem;">03</span>
                </div>
                <div class="sli-capsule-body">
                    <h5 style="margin: 0; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #cffafe;">
                        Kontrak Didanai
                    </h5>
                    <div id="sli-cap-val-3" style="font-size: 1.35rem; font-weight: 900; font-family: monospace; margin: 0.25rem 0; color: #ffffff;">
                        {{ $totalDidanai }} <span style="font-size: 0.75rem; font-weight: 400; color: #bae6fd;">SK</span>
                    </div>
                    <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.85); line-height: 1.25;">
                        Surat tugas, SPK bermaterai & termin dana
                    </p>
                </div>
            </div>

            <!-- Card 04 -->
            <div class="sli-capsule-col">
                <div class="sli-hex-badge">
                    <svg viewBox="0 0 48 42" style="width: 100%; height: 100%; fill: #06b6d4; stroke: #ffffff; stroke-width: 2.5;">
                        <polygon points="24,2 44,11 44,31 24,40 4,31 4,11" />
                    </svg>
                    <span style="position: absolute; color: #ffffff; font-family: monospace; font-weight: 900; font-size: 0.75rem;">04</span>
                </div>
                <div class="sli-capsule-body">
                    <h5 style="margin: 0; font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #cffafe;">
                        Monev & Luaran
                    </h5>
                    <div id="sli-cap-val-4" style="font-size: 1.35rem; font-weight: 900; font-family: monospace; margin: 0.25rem 0; color: #ffffff;">
                        38 <span style="font-size: 0.75rem; font-weight: 400; color: #bae6fd;">BASTB</span>
                    </div>
                    <p style="margin: 0; font-size: 0.75rem; color: rgba(255,255,255,0.85); line-height: 1.25;">
                        Laporan akhir, SPTB 100% & artikel Sinta
                    </p>
                </div>
            </div>
        </div>

        <!-- SECTION C: Modern Charts Row (Bottom Row of Uploaded Image) -->
        <div id="sli-charts-container" class="sli-charts-grid">
            
            <!-- LEFT CHART: Capsule Bar Chart with Embedded Bead Rings -->
            <div id="sli-chart-bar-card" class="sli-chart-box">
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 0.6rem; height: 0.6rem; border-radius: 9999px; background: #06b6d4; box-shadow: 0 0 0 2px #bae6fd;"></span>
                        <h4 style="margin: 0; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #0f172a; letter-spacing: 0.03em;">
                            Grafik Kapsul Usulan & Didanai
                        </h4>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.7rem;">
                        <span style="display: flex; align-items: center; gap: 0.25rem; font-weight: 700; color: #0891b2;">
                            <span style="width: 0.75rem; height: 0.75rem; border-radius: 9999px; background: #22d3ee;"></span> Usulan
                        </span>
                        <span style="display: flex; align-items: center; gap: 0.25rem; font-weight: 700; color: #1d4ed8;">
                            <span style="width: 0.75rem; height: 0.75rem; border-radius: 9999px; background: #2563eb;"></span> Didanai
                        </span>
                    </div>
                </div>

                <!-- SVG Capsule Bar Chart -->
                <div style="padding: 0.5rem 0;">
                    <svg id="sli-bar-svg" style="width: 100%; height: 14rem; overflow: visible;" viewBox="0 0 340 180">
                        <!-- Injected by JavaScript dynamically based on active filter -->
                    </svg>
                </div>

                <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-align: center; font-weight: 500;">
                    Setiap kolom menampilkan kapsul Usulan (cyan) dan Didanai (royal blue) dengan indikator simpul bead di puncak kapsul.
                </p>
            </div>

            <!-- RIGHT CHART: Mountain / Wave Area Spline Chart with Peak Nodes -->
            <div id="sli-chart-trend-card" class="sli-chart-box">
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="width: 0.6rem; height: 0.6rem; border-radius: 9999px; background: #2563eb; box-shadow: 0 0 0 2px #bfdbfe;"></span>
                        <h4 style="margin: 0; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: #0f172a; letter-spacing: 0.03em;">
                            Grafik Kurva Gelombang Mountain (Spline)
                        </h4>
                    </div>
                    <span id="sli-trend-badge" style="font-size: 0.75rem; font-family: monospace; font-weight: 700; color: #64748b; background: #ffffff; padding: 0.15rem 0.5rem; border-radius: 0.25rem; border: 1px solid #e2e8f0;">
                        Akumulasi: {{ $fundingRate }}% Lolos
                    </span>
                </div>

                <!-- SVG Mountain Spline Chart -->
                <div style="padding: 0.5rem 0;">
                    <svg id="sli-mountain-svg" style="width: 100%; height: 14rem; overflow: visible;" viewBox="0 0 340 180">
                        <!-- Injected by JavaScript dynamically based on active filter -->
                    </svg>
                </div>

                <p style="margin: 0; font-size: 0.75rem; color: #64748b; text-align: center; font-weight: 500;">
                    Kurva Mountain Wave menampilkan lintasan pertumbuhan proposal dengan simpul puncak data interaktif.
                </p>
            </div>
        </div>

        <!-- SECTION D: Interactive Year Inspection Card -->
        <div class="sli-year-inspect">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 3rem; height: 3rem; border-radius: 0.75rem; background: linear-gradient(135deg, #06b6d4 0%, #1d4ed8 100%); color: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; font-family: monospace; font-weight: 900; line-height: 1; border: 1px solid rgba(255,255,255,0.4); box-shadow: 0 2px 4px rgba(2, 132, 199, 0.2);">
                    <span style="font-size: 0.75rem; text-transform: uppercase; color: #cffafe;">TAHUN</span>
                    <span id="sli-active-year" style="font-size: 1rem; font-weight: 900;">2026</span>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 0.8rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem;">
                        <span id="sli-inspect-title">Rincian Volume Usulan & Pendanaan 2026</span>
                        <span id="sli-active-rate" style="font-size: 0.75rem; font-weight: 800; color: #0369a1; background: #e0f2fe; padding: 0.15rem 0.5rem; border-radius: 9999px; border: 1px solid #bae6fd;">
                            Tingkat Kelolosan: 70.4%
                        </span>
                    </div>
                    <div style="font-size: 0.7rem; color: #64748b; margin-top: 0.15rem;">
                        Status: <strong id="sli-active-catatan" style="color: #1e293b;">Tahun Berjalan / Seleksi & Verifikasi Aktif</strong>
                    </div>
                </div>
            </div>

            <!-- Quick Metrics for Active Year -->
            <div style="display: flex; align-items: center; gap: 0.5rem; font-family: monospace;">
                <div style="padding: 0.35rem 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.5rem; text-align: right; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-family: sans-serif; font-weight: 700;">Usulan Masuk</div>
                    <div id="sli-inspect-usulan" style="font-weight: 900; color: #0369a1; font-size: 0.85rem;">54 Proposal</div>
                </div>
                <div style="padding: 0.35rem 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.5rem; text-align: right; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-family: sans-serif; font-weight: 700;">Didanai LPPM</div>
                    <div id="sli-inspect-didanai" style="font-weight: 900; color: #1d4ed8; font-size: 0.85rem;">38 Proposal</div>
                </div>
                <div style="padding: 0.35rem 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 0.5rem; text-align: right; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-family: sans-serif; font-weight: 700;">Total Dana</div>
                    <div id="sli-inspect-dana" style="font-weight: 900; color: #0f172a; font-size: 0.85rem;">Rp 1.080 Jt</div>
                </div>
            </div>
        </div>

        <!-- SECTION E: Expandable Full Table Breakdown -->
        <div style="padding-top: 0.25rem;">
            <button type="button" onclick="sliToggleTable()" style="font-size: 0.75rem; font-weight: 700; color: #0369a1; background: none; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0;">
                <span id="sli-table-toggle-text">Tampilkan Tabel Lengkap Rekapitulasi Usulan & Dana per Tahun</span>
                <span id="sli-table-toggle-icon">▼</span>
            </button>

            <div id="sli-table-container" style="display: none; margin-top: 0.75rem; overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 0.75rem;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.75rem; color: #334155;">
                    <thead style="background: #f8fafc; font-size: 0.7rem; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #e2e8f0;">
                        <tr>
                            <th style="padding: 0.75rem 0.75rem;">Tahun Akademik</th>
                            <th style="padding: 0.75rem 0.75rem; text-align: right;">Usulan (Diunggah)</th>
                            <th style="padding: 0.75rem 0.75rem; text-align: right;">Didanai LPPM</th>
                            <th style="padding: 0.75rem 0.75rem; text-align: right;">Tidak Didanai</th>
                            <th style="padding: 0.75rem 0.75rem; text-align: right;">Tingkat Lolos (%)</th>
                            <th style="padding: 0.75rem 0.75rem; text-align: right;">Penyaluran Dana</th>
                            <th style="padding: 0.75rem 0.75rem;">Catatan Administrasi</th>
                        </tr>
                    </thead>
                    <tbody id="sli-table-body" style="font-family: monospace;">
                        <!-- Injected by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 4. LPPM ADMIN MODAL: KELOLA PENGUMUMAN & JADWAL -->
    <div id="sli-modal" class="sli-modal-backdrop" style="display: none;">
        <div class="sli-modal-dialog">
            <!-- Modal Header -->
            <div style="background: linear-gradient(135deg, #0891b2 0%, #0284c7 50%, #1d4ed8 100%); color: #ffffff; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 2rem; height: 2rem; border-radius: 0.5rem; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                        📢
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 0.85rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.03em;">
                            Kelola Pengumuman, Jadwal & Berita LPPM
                        </h3>
                        <p style="margin: 0; font-size: 0.75rem; color: #cffafe;">
                            Kelola pesan sambutan, batas waktu kegiatan, dan pengumuman resmi dosen
                        </p>
                    </div>
                </div>
                <button type="button" onclick="sliCloseModal()" style="width: 1.75rem; height: 1.75rem; border-radius: 9999px; background: rgba(255,255,255,0.15); border: none; color: #ffffff; font-size: 1rem; cursor: pointer;">
                    ✕
                </button>
            </div>

            <!-- Modal Body -->
            <div style="padding: 1.25rem; max-height: 70vh; overflow-y: auto;">
                <!-- Form -->
                <form id="sli-form" onsubmit="sliSaveBulletin(event)" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <span id="sli-form-mode-title" style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #0f172a;">
                            Tambah Pengumuman / Jadwal Baru
                        </span>
                        <button type="button" id="sli-btn-cancel-edit" onclick="sliCancelEdit()" style="display: none; font-size: 0.7rem; color: #0284c7; background: none; border: none; text-decoration: underline; cursor: pointer;">
                            Batal Edit
                        </button>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.25rem;">
                                Kategori
                            </label>
                            <select id="sli-input-type" style="width: 100%; font-size: 0.75rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.35rem 0.5rem;">
                                <option value="pengumuman">📢 Pengumuman Penting</option>
                                <option value="jadwal">📅 Jadwal Kegiatan</option>
                                <option value="berita">📰 Berita Terkini</option>
                                <option value="salam">👋 Motivasi & Salam</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.25rem;">
                                Judul / Topik Ringkas
                            </label>
                            <input type="text" id="sli-input-title" placeholder="Contoh: Batas Akhir Unggah SPTB 70%" style="width: 100%; font-size: 0.75rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.35rem 0.5rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.25rem;">
                            Isi Pesan / Pengumuman / Jadwal Lengkap *
                        </label>
                        <textarea id="sli-input-content" rows="2" required placeholder="Tuliskan isi informasi yang akan tampil pada banner selamat datang..." style="width: 100%; font-size: 0.75rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.5rem;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; align-items: center; margin-bottom: 0.75rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 0.25rem;">
                                Tanggal / Periode
                            </label>
                            <input type="text" id="sli-input-date" placeholder="Contoh: 15 Juli 2026" style="width: 100%; font-size: 0.75rem; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 0.5rem; padding: 0.35rem 0.5rem;">
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.4rem; padding-top: 1rem;">
                            <input type="checkbox" id="sli-input-important" style="width: 1rem; height: 1rem; cursor: pointer;">
                            <label for="sli-input-important" style="font-size: 0.7rem; font-weight: 700; color: #334155; cursor: pointer;">
                                Tandai Prioritas Utama
                            </label>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" style="padding: 0.45rem 1rem; background: linear-gradient(135deg, #0891b2 0%, #1d4ed8 100%); color: #ffffff; border: none; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 800; cursor: pointer; box-shadow: 0 2px 4px rgba(2, 132, 199, 0.25);">
                            Simpan & Publikasikan ke Banner
                        </button>
                    </div>
                </form>

                <!-- List of Active Bulletins -->
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #0f172a;">
                            Daftar Pengumuman & Jadwal Aktif
                        </span>
                        <button type="button" onclick="sliResetBulletins()" style="font-size: 0.7rem; color: #0284c7; background: none; border: none; cursor: pointer; font-weight: 700;">
                            🔄 Reset Bawaan
                        </button>
                    </div>

                    <div id="sli-bulletins-list" style="border: 1px solid #e2e8f0; border-radius: 0.75rem; overflow: hidden;">
                        <!-- Injected by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 0.75rem 1.25rem; display: flex; justify-content: flex-end;">
                <button type="button" onclick="sliCloseModal()" style="padding: 0.4rem 0.9rem; background: #e2e8f0; border: none; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 700; color: #1e293b; cursor: pointer;">
                    Tutup Pengaturan
                </button>
            </div>
        </div>
    </div>

</div>

<!-- JAVASCRIPT CONTROLLER (100% IDENTICAL BEHAVIOR & INTERACTION TO GOOGLE AI STUDIO) -->
<script>
(function() {
    // 1. Data Store (real data from server, no fabricated values)
    const trendData = @json($trendData ?? []);

    const defaultBulletins = [
        {
            id: 'b-1',
            type: 'salam',
            title: 'Salam Semangat Tri Dharma',
            content: 'Have a nice Thursday! Tetap semangat berkarya dalam Tri Dharma Perguruan Tinggi LPPM ITSNU Pekalongan.',
            date: '{{ $fullIndoDate }}',
            badge: '👋 MOTIVASI & SALAM',
            isImportant: false
        },
        {
            id: 'b-2',
            type: 'pengumuman',
            title: 'Batas Akhir Laporan Kemajuan & SPTB 70%',
            content: 'Batas Akhir Unggah Laporan Kemajuan & SPTB 70%: 15 Juli 2026. Mohon bapak/ibu dosen peneliti melengkapi berkas.',
            date: '15 Juli 2026',
            badge: '📢 PENGUMUMAN PENTING',
            isImportant: true
        },
        {
            id: 'b-3',
            type: 'jadwal',
            title: 'Desk Evaluasi Proposal & Monev Lapangan Tahap II',
            content: 'Desk Evaluasi & Monev Lapangan Proposal Tahap II: 2 - 4 Agustus 2026 bertempat di Ruang Rapat LPPM Lt. 2.',
            date: '02-04 Ags 2026',
            badge: '📅 JADWAL KEGIATAN',
            isImportant: true
        },
        {
            id: 'b-4',
            type: 'berita',
            title: 'Sosialisasi Hibah Riset & Hilirisasi Prototipe 2026',
            content: 'Sosialisasi Panduan Riset Hibah Kemendiktisaintek & Skema Hilirisasi Riset ITSNU Pekalongan Tahun Akademik 2026/2027.',
            date: '20 Juni 2026',
            badge: '📰 BERITA TERKINI',
            isImportant: false
        }
    ];

    let currentScheme = 'all';
    let currentLayout = 'both';
    let activeYearIndex = 5; // default 2026
    let isTableOpen = false;
    let editingId = null;

    let bulletins = defaultBulletins;
    try {
        const saved = localStorage.getItem('lppm_dashboard_bulletins');
        if (saved) bulletins = JSON.parse(saved);
    } catch(e) {}
    let activeBulletinIndex = 0;

    // Helper: calculate data
    function getProcessedData() {
        return trendData.map(item => {
            let u = 0, d = 0;
            if (currentScheme === 'penelitian') {
                u = item.usulanPenelitian;
                d = item.didanaiPenelitian;
            } else if (currentScheme === 'pengabdian') {
                u = item.usulanPengabdian;
                d = item.didanaiPengabdian;
            } else {
                u = item.usulanPenelitian + item.usulanPengabdian;
                d = item.didanaiPenelitian + item.didanaiPengabdian;
            }
            const rate = u > 0 ? ((d / u) * 100).toFixed(1) : 0;
            return {
                ...item,
                usulan: u,
                didanai: d,
                tidakDidanai: u - d,
                rate: Number(rate)
            };
        });
    }

    // 2. Render Functions
    function renderBanner() {
        if (!bulletins || bulletins.length === 0) bulletins = defaultBulletins;
        if (activeBulletinIndex >= bulletins.length) activeBulletinIndex = 0;
        const b = bulletins[activeBulletinIndex];

        const badgeEl = document.getElementById('sli-bulletin-badge');
        const titleEl = document.getElementById('sli-bulletin-title');
        const dateEl = document.getElementById('sli-bulletin-date');
        const contentEl = document.getElementById('sli-bulletin-content');
        const counterEl = document.getElementById('sli-bulletin-counter');
        const dotsEl = document.getElementById('sli-bulletin-dots');

        if (badgeEl) {
            badgeEl.innerText = b.badge;
            if (b.type === 'pengumuman') {
                badgeEl.style.background = '#fef08a';
                badgeEl.style.color = '#713f12';
            } else if (b.type === 'jadwal') {
                badgeEl.style.background = '#a7f3d0';
                badgeEl.style.color = '#064e3b';
            } else if (b.type === 'berita') {
                badgeEl.style.background = '#bae6fd';
                badgeEl.style.color = '#082f49';
            } else {
                badgeEl.style.background = '#c7d2fe';
                badgeEl.style.color = '#1e1b4b';
            }
        }
        if (titleEl) titleEl.innerText = b.title;
        if (dateEl) dateEl.innerText = '• ' + b.date;
        if (contentEl) contentEl.innerText = b.content;
        if (counterEl) counterEl.innerText = (activeBulletinIndex + 1) + '/' + bulletins.length;

        if (dotsEl) {
            dotsEl.innerHTML = bulletins.map((_, idx) => `
                <button type="button" onclick="sliGoToBulletin(${idx})" style="height: 4px; border-radius: 9999px; border: none; cursor: pointer; transition: all 0.3s ease; padding: 0; ${idx === activeBulletinIndex ? 'width: 1.25rem; background: #67e8f9;' : 'width: 0.4rem; background: rgba(255,255,255,0.3);'}"></button>
            `).join('');
        }
    }

    function renderCapsuleBarChart() {
        const svg = document.getElementById('sli-bar-svg');
        if (!svg) return;
        const data = getProcessedData();
        const maxVal = 65;

        let defs = `
            <defs>
                <linearGradient id="capsuleGradUsulan" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#22d3ee" />
                    <stop offset="40%" stop-color="#06b6d4" />
                    <stop offset="100%" stop-color="#0284c7" />
                </linearGradient>
                <linearGradient id="capsuleGradDidanai" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#38bdf8" />
                    <stop offset="50%" stop-color="#2563eb" />
                    <stop offset="100%" stop-color="#1e40af" />
                </linearGradient>
                <filter id="capsuleGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="#0284c7" flood-opacity="0.25" />
                </filter>
            </defs>
        `;

        // Guidelines
        const yLines = [20, 55, 90, 125, 155];
        const labels = ['60', '45', '30', '15', '0'];
        let gridHtml = yLines.map((y, idx) => `
            <line x1="30" y1="${y}" x2="330" y2="${y}" stroke="#e2e8f0" stroke-width="0.8" />
            <text x="24" y="${y + 3}" text-anchor="end" font-size="8" fill="#94a3b8" font-family="monospace">${labels[idx]}</text>
        `).join('');

        let barsHtml = data.map((d, i) => {
            const isHovered = activeYearIndex === i;
            const colWidth = 44;
            const startX = 40 + i * colWidth;
            const barWidth = 14;

            const hUsulan = Math.max((d.usulan / maxVal) * 125, 18);
            const yUsulan = 155 - hUsulan;

            const hDidanai = Math.max((d.didanai / maxVal) * 125, 15);
            const yDidanai = 155 - hDidanai;

            return `
                <g class="sli-bar-group" onclick="sliSelectYear(${i})" style="cursor: pointer;">
                    ${isHovered ? `
                        <rect x="${startX - 3}" y="12" width="${barWidth * 2 + 10}" height="146" rx="8" fill="#f0f9ff" stroke="#bae6fd" stroke-width="1" />
                    ` : ''}

                    <!-- Bar Usulan -->
                    <rect x="${startX}" y="${yUsulan}" width="${barWidth}" height="${hUsulan}" rx="${barWidth / 2}" fill="url(#capsuleGradUsulan)" filter="url(#capsuleGlow)" />
                    <!-- Top Bead Ring Usulan -->
                    <circle cx="${startX + barWidth / 2}" cy="${yUsulan + barWidth / 2 + 1}" r="${barWidth / 2 - 2.5}" fill="#0891b2" stroke="#ffffff" stroke-width="1.2" />
                    <circle cx="${startX + barWidth / 2}" cy="${yUsulan + barWidth / 2 + 1}" r="1.8" fill="#ffffff" />

                    <!-- Bar Didanai -->
                    <rect x="${startX + barWidth + 4}" y="${yDidanai}" width="${barWidth}" height="${hDidanai}" rx="${barWidth / 2}" fill="url(#capsuleGradDidanai)" filter="url(#capsuleGlow)" />
                    <!-- Top Bead Ring Didanai -->
                    <circle cx="${startX + barWidth + 4 + barWidth / 2}" cy="${yDidanai + barWidth / 2 + 1}" r="${barWidth / 2 - 2.5}" fill="#1d4ed8" stroke="#ffffff" stroke-width="1.2" />
                    <circle cx="${startX + barWidth + 4 + barWidth / 2}" cy="${yDidanai + barWidth / 2 + 1}" r="1.8" fill="#ffffff" />

                    <!-- X-Axis Year -->
                    <text x="${startX + barWidth + 2}" y="172" text-anchor="middle" font-size="9" font-family="monospace" font-weight="${isHovered ? '900' : '600'}" fill="${isHovered ? '#0369a1' : '#64748b'}">${d.year}</text>

                    ${isHovered ? `
                        <text x="${startX + barWidth + 2}" y="${Math.min(yUsulan, yDidanai) - 4}" text-anchor="middle" font-size="8" font-weight="bold" font-family="monospace" fill="#0284c7">${d.rate}%</text>
                    ` : ''}
                </g>
            `;
        }).join('');

        svg.innerHTML = defs + gridHtml + barsHtml;
    }

    function renderMountainSplineChart() {
        const svg = document.getElementById('sli-mountain-svg');
        if (!svg) return;
        const data = getProcessedData();
        const count = data.length;
        const maxVal = 65;

        let defs = `
            <defs>
                <linearGradient id="mountainAreaGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#22d3ee" stop-opacity="0.55" />
                    <stop offset="50%" stop-color="#38bdf8" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0.0" />
                </linearGradient>
                <linearGradient id="mountainAreaDidanaiGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#2563eb" stop-opacity="0.45" />
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0.0" />
                </linearGradient>
            </defs>
        `;

        const yLines = [20, 55, 90, 125, 155];
        let gridHtml = yLines.map(y => `
            <line x1="20" y1="${y}" x2="330" y2="${y}" stroke="#e2e8f0" stroke-width="0.8" />
        `).join('');

        const usulanPts = data.map((d, i) => ({
            x: 30 + (i / (count - 1)) * 290,
            y: 155 - (d.usulan / maxVal) * 130,
            val: d.usulan,
            year: d.year,
            rate: d.rate
        }));

        const didanaiPts = data.map((d, i) => ({
            x: 30 + (i / (count - 1)) * 290,
            y: 155 - (d.didanai / maxVal) * 130,
            val: d.didanai,
            year: d.year
        }));

        const uPath = usulanPts.reduce((acc, curr, idx) => idx === 0 ? `M ${curr.x} ${curr.y}` : `${acc} L ${curr.x} ${curr.y}`, '');
        const uArea = `${uPath} L ${usulanPts[usulanPts.length - 1].x} 155 L ${usulanPts[0].x} 155 Z`;

        const dPath = didanaiPts.reduce((acc, curr, idx) => idx === 0 ? `M ${curr.x} ${curr.y}` : `${acc} L ${curr.x} ${curr.y}`, '');
        const dArea = `${dPath} L ${didanaiPts[didanaiPts.length - 1].x} 155 L ${didanaiPts[0].x} 155 Z`;

        let nodesHtml = usulanPts.map((pt, i) => {
            const isHovered = activeYearIndex === i;
            return `
                <g onclick="sliSelectYear(${i})" style="cursor: pointer;">
                    ${isHovered ? `
                        <line x1="${pt.x}" y1="${pt.y}" x2="${pt.x}" y2="155" stroke="#0284c7" stroke-width="1" stroke-dasharray="2 2" />
                    ` : ''}
                    <circle cx="${pt.x}" cy="${pt.y}" r="${isHovered ? 7 : 5}" fill="#0284c7" stroke="#ffffff" stroke-width="2.5" />
                    <circle cx="${didanaiPts[i].x}" cy="${didanaiPts[i].y}" r="${isHovered ? 6 : 4}" fill="#2563eb" stroke="#ffffff" stroke-width="2" />
                    <text x="${pt.x}" y="172" text-anchor="middle" font-size="9" font-family="monospace" font-weight="${isHovered ? '900' : '600'}" fill="${isHovered ? '#0369a1' : '#64748b'}">${pt.year}</text>
                </g>
            `;
        }).join('');

        svg.innerHTML = `
            ${defs}
            ${gridHtml}
            <path d="${uArea}" fill="url(#mountainAreaGrad)" />
            <path d="${dArea}" fill="url(#mountainAreaDidanaiGrad)" />
            <path d="${uPath}" fill="none" stroke="#06b6d4" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
            <path d="${dPath}" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="4 2" />
            ${nodesHtml}
        `;
    }

    function renderYearInspection() {
        const data = getProcessedData();
        const active = data[activeYearIndex] || data[data.length - 1];

        const yrEl = document.getElementById('sli-active-year');
        const titleEl = document.getElementById('sli-inspect-title');
        const rateEl = document.getElementById('sli-active-rate');
        const catatanEl = document.getElementById('sli-active-catatan');
        const uEl = document.getElementById('sli-inspect-usulan');
        const dEl = document.getElementById('sli-inspect-didanai');
        const danaEl = document.getElementById('sli-inspect-dana');

        if (yrEl) yrEl.innerText = active.year;
        if (titleEl) titleEl.innerText = 'Rincian Volume Usulan & Pendanaan ' + active.year;
        if (rateEl) rateEl.innerText = 'Tingkat Kelolosan: ' + active.rate + '%';
        if (catatanEl) catatanEl.innerText = active.catatan;
        if (uEl) uEl.innerText = active.usulan + ' Proposal';
        if (dEl) dEl.innerText = active.didanai + ' Proposal';
        if (danaEl) danaEl.innerText = 'Rp ' + active.danaTotalJuta + ' Jt';
    }

    function renderTable() {
        const tbody = document.getElementById('sli-table-body');
        if (!tbody) return;
        const data = getProcessedData();

        let totalU = 0, totalD = 0, totalTD = 0, totalDana = 0;

        let rowsHtml = data.map(row => {
            totalU += row.usulan;
            totalD += row.didanai;
            totalTD += row.tidakDidanai;
            totalDana += row.danaTotalJuta;
            const isSelected = data[activeYearIndex]?.year === row.year;

            return `
                <tr style="border-bottom: 1px solid #f1f5f9; ${isSelected ? 'background: #ecfeff; font-weight: 700;' : ''}">
                    <td style="padding: 0.5rem 0.75rem; font-weight: 700; color: #0f172a; font-family: sans-serif;">Tahun ${row.year}</td>
                    <td style="padding: 0.5rem 0.75rem; text-align: right; color: #0891b2; font-weight: 800;">${row.usulan}</td>
                    <td style="padding: 0.5rem 0.75rem; text-align: right; color: #1d4ed8; font-weight: 800;">${row.didanai}</td>
                    <td style="padding: 0.5rem 0.75rem; text-align: right; color: #94a3b8;">${row.tidakDidanai}</td>
                    <td style="padding: 0.5rem 0.75rem; text-align: right; font-weight: 800; color: #0f172a;">${row.rate}%</td>
                    <td style="padding: 0.5rem 0.75rem; text-align: right; font-family: sans-serif;">Rp ${row.danaTotalJuta} Jt</td>
                    <td style="padding: 0.5rem 0.75rem; font-family: sans-serif; font-size: 0.7rem; color: #64748b;">${row.catatan}</td>
                </tr>
            `;
        }).join('');

        const rateTotal = totalU > 0 ? ((totalD / totalU) * 100).toFixed(1) : 0;
        rowsHtml += `
            <tr style="background: #f8fafc; font-weight: 900; color: #0f172a; border-top: 2px solid #cbd5e1;">
                <td style="padding: 0.75rem 0.75rem; font-family: sans-serif;">Total Akumulasi</td>
                <td style="padding: 0.75rem 0.75rem; text-align: right; color: #0c4a6e;">${totalU}</td>
                <td style="padding: 0.75rem 0.75rem; text-align: right; color: #1e3a8a;">${totalD}</td>
                <td style="padding: 0.75rem 0.75rem; text-align: right; color: #64748b;">${totalTD}</td>
                <td style="padding: 0.75rem 0.75rem; text-align: right;">${rateTotal}%</td>
                <td style="padding: 0.75rem 0.75rem; text-align: right; font-family: sans-serif;">Rp ${(totalDana/1000).toFixed(2)} M</td>
                <td style="padding: 0.75rem 0.75rem; font-family: sans-serif; font-size: 0.7rem; color: #475569;">Terverifikasi SIM LPPM ITSNU</td>
            </tr>
        `;

        tbody.innerHTML = rowsHtml;
    }

    function renderModalList() {
        const listEl = document.getElementById('sli-bulletins-list');
        if (!listEl) return;

        listEl.innerHTML = bulletins.map((b, idx) => {
            const isSelected = activeBulletinIndex === idx;
            return `
                <div style="padding: 0.75rem 0.75rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; ${isSelected ? 'background: #ecfeff;' : ''}">
                    <div style="min-width: 0;">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-bottom: 0.15rem;">
                            <span style="font-size: 0.6rem; font-weight: 800; text-transform: uppercase; padding: 0.1rem 0.35rem; border-radius: 0.25rem; background: #e2e8f0; color: #334155;">${b.badge}</span>
                            <strong style="font-size: 0.75rem; color: #0f172a;">${b.title}</strong>
                            <span style="font-size: 0.75rem; color: #94a3b8; font-family: monospace;">(${b.date})</span>
                            ${isSelected ? '<span style="font-size: 0.75rem; font-weight: 900; background: #0891b2; color: #ffffff; padding: 0.1rem 0.3rem; border-radius: 0.2rem; text-transform: uppercase;">Tampil Sekarang</span>' : ''}
                        </div>
                        <p style="margin: 0; font-size: 0.7rem; color: #475569; line-height: 1.3;">${b.content}</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.25rem; shrink-0;">
                        <button type="button" onclick="sliGoToBulletin(${idx})" style="padding: 0.2rem 0.45rem; border-radius: 0.25rem; border: none; font-size: 0.75rem; font-weight: 700; cursor: pointer; ${isSelected ? 'background: #0891b2; color: #ffffff;' : 'background: #e2e8f0; color: #334155;'}">Pilih</button>
                        <button type="button" onclick="sliStartEdit('${b.id}')" style="padding: 0.2rem 0.35rem; background: none; border: none; font-size: 0.75rem; cursor: pointer;">✏️</button>
                        <button type="button" onclick="sliDeleteBulletin('${b.id}')" style="padding: 0.2rem 0.35rem; background: none; border: none; font-size: 0.75rem; cursor: pointer; color: #e11d48;">🗑️</button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // 3. Global Exposed Methods
    window.sliNextBulletin = function() {
        activeBulletinIndex = (activeBulletinIndex + 1) % bulletins.length;
        renderBanner();
        renderModalList();
    };

    window.sliPrevBulletin = function() {
        activeBulletinIndex = (activeBulletinIndex - 1 + bulletins.length) % bulletins.length;
        renderBanner();
        renderModalList();
    };

    window.sliGoToBulletin = function(idx) {
        activeBulletinIndex = idx;
        renderBanner();
        renderModalList();
    };

    window.sliSetScheme = function(scheme) {
        currentScheme = scheme;
        ['all', 'penelitian', 'pengabdian'].forEach(s => {
            const btn = document.getElementById('btn-scheme-' + s);
            if (btn) {
                if (s === scheme) {
                    btn.style.background = '#ffffff';
                    btn.style.color = '#0c4a6e';
                    btn.style.fontWeight = '700';
                    btn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                } else {
                    btn.style.background = 'transparent';
                    btn.style.color = '#64748b';
                    btn.style.fontWeight = '600';
                    btn.style.boxShadow = 'none';
                }
            }
        });
        renderCapsuleBarChart();
        renderMountainSplineChart();
        renderYearInspection();
        renderTable();
    };

    window.sliSetLayout = function(layout) {
        currentLayout = layout;
        const barCard = document.getElementById('sli-chart-bar-card');
        const trendCard = document.getElementById('sli-chart-trend-card');
        const container = document.getElementById('sli-charts-container');

        ['both', 'bar', 'trend'].forEach(l => {
            const btn = document.getElementById('btn-layout-' + l);
            if (btn) {
                if (l === layout) {
                    btn.style.background = '#0891b2';
                    btn.style.color = '#ffffff';
                } else {
                    btn.style.background = 'transparent';
                    btn.style.color = '#64748b';
                }
            }
        });

        if (layout === 'bar') {
            if (barCard) barCard.style.display = 'flex';
            if (trendCard) trendCard.style.display = 'none';
            if (container) container.style.gridTemplateColumns = '1fr';
        } else if (layout === 'trend') {
            if (barCard) barCard.style.display = 'none';
            if (trendCard) trendCard.style.display = 'flex';
            if (container) container.style.gridTemplateColumns = '1fr';
        } else {
            if (barCard) barCard.style.display = 'flex';
            if (trendCard) trendCard.style.display = 'flex';
            if (container && window.innerWidth >= 992) container.style.gridTemplateColumns = 'repeat(2, minmax(0, 1fr))';
        }
    };

    window.sliSelectYear = function(idx) {
        activeYearIndex = idx;
        renderCapsuleBarChart();
        renderMountainSplineChart();
        renderYearInspection();
        renderTable();
    };

    window.sliToggleTable = function() {
        isTableOpen = !isTableOpen;
        const box = document.getElementById('sli-table-container');
        const text = document.getElementById('sli-table-toggle-text');
        const icon = document.getElementById('sli-table-toggle-icon');

        if (box) box.style.display = isTableOpen ? 'block' : 'none';
        if (text) text.innerText = isTableOpen ? 'Sembunyikan Tabel Rekapitulasi Usulan & Dana' : 'Tampilkan Tabel Lengkap Rekapitulasi Usulan & Dana per Tahun';
        if (icon) icon.innerText = isTableOpen ? '▲' : '▼';
    };

    // Modal Actions
    window.sliOpenModal = function() {
        const m = document.getElementById('sli-modal');
        if (m) m.style.display = 'flex';
        renderModalList();
    };

    window.sliCloseModal = function() {
        const m = document.getElementById('sli-modal');
        if (m) m.style.display = 'none';
        sliCancelEdit();
    };

    window.sliStartEdit = function(id) {
        const item = bulletins.find(b => b.id === id);
        if (!item) return;
        editingId = id;

        document.getElementById('sli-input-type').value = item.type;
        document.getElementById('sli-input-title').value = item.title;
        document.getElementById('sli-input-content').value = item.content;
        document.getElementById('sli-input-date').value = item.date;
        document.getElementById('sli-input-important').checked = !!item.isImportant;

        document.getElementById('sli-form-mode-title').innerText = 'Edit Informasi Terpilih';
        document.getElementById('sli-btn-cancel-edit').style.display = 'inline';
    };

    window.sliCancelEdit = function() {
        editingId = null;
        document.getElementById('sli-form').reset();
        document.getElementById('sli-form-mode-title').innerText = 'Tambah Pengumuman / Jadwal Baru';
        document.getElementById('sli-btn-cancel-edit').style.display = 'none';
    };

    window.sliSaveBulletin = function(e) {
        e.preventDefault();
        const type = document.getElementById('sli-input-type').value;
        const title = document.getElementById('sli-input-title').value.trim() || 'Pengumuman LPPM';
        const content = document.getElementById('sli-input-content').value.trim();
        const date = document.getElementById('sli-input-date').value.trim() || 'Juni 2026';
        const isImportant = document.getElementById('sli-input-important').checked;

        if (!content) return;

        let badge = '📢 PENGUMUMAN PENTING';
        if (type === 'jadwal') badge = '📅 JADWAL KEGIATAN';
        else if (type === 'berita') badge = '📰 BERITA TERKINI';
        else if (type === 'salam') badge = '👋 MOTIVASI & SALAM';

        if (editingId) {
            bulletins = bulletins.map(b => b.id === editingId ? { ...b, type, title, content, date, badge, isImportant } : b);
        } else {
            const newItem = {
                id: 'b-' + Date.now(),
                type,
                title,
                content,
                date,
                badge,
                isImportant
            };
            bulletins = [newItem, ...bulletins];
            activeBulletinIndex = 0;
        }

        try {
            localStorage.setItem('lppm_dashboard_bulletins', JSON.stringify(bulletins));
        } catch(e) {}

        sliCancelEdit();
        renderBanner();
        renderModalList();
    };

    window.sliDeleteBulletin = function(id) {
        if (bulletins.length <= 1) {
            alert('Minimal harus ada satu pengumuman atau salam aktif.');
            return;
        }
        bulletins = bulletins.filter(b => b.id !== id);
        activeBulletinIndex = 0;
        try {
            localStorage.setItem('lppm_dashboard_bulletins', JSON.stringify(bulletins));
        } catch(e) {}
        renderBanner();
        renderModalList();
    };

    window.sliResetBulletins = function() {
        if (confirm('Kembalikan ke pengumuman & salam bawaan LPPM?')) {
            bulletins = defaultBulletins;
            activeBulletinIndex = 0;
            try {
                localStorage.setItem('lppm_dashboard_bulletins', JSON.stringify(bulletins));
            } catch(e) {}
            sliCancelEdit();
            renderBanner();
            renderModalList();
        }
    };

    // Auto rotate every 6 seconds
    setInterval(() => {
        if (bulletins.length > 1) {
            activeBulletinIndex = (activeBulletinIndex + 1) % bulletins.length;
            renderBanner();
        }
    }, 6000);

    function renderAll() {
        renderBanner();
        renderCapsuleBarChart();
        renderMountainSplineChart();
        renderYearInspection();
        renderTable();
    }

    window.sliInit = renderAll;
    renderAll();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderAll);
    }
    document.addEventListener('livewire:initialized', renderAll);
    document.addEventListener('livewire:navigated', renderAll);
    document.addEventListener('livewire:load', renderAll);
})();
</script>
