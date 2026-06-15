<div>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}

    {{-- Alpine.js root: holds all preview state locally for instant update --}}
    <div
        x-data="{
            activePdfTab: 'layout',

            {{-- Mirror all Livewire values into Alpine for instant preview (no roundtrip) --}}
            font:             '{{ addslashes($pdfFontFamily) }}',
            fontSize:         {{ $pdfBodyFontSize }},
            lineHeight:       '{{ $pdfLineHeight }}',
            paraSpacing:      {{ $pdfParagraphSpacing }},
            paraIndent:       {{ $pdfParagraphIndent }},
            showLogo:         {{ $pdfShowLogo ? 'true' : 'false' }},
            logoPos:          '{{ $pdfLogoPosition }}',
            logoSize:         {{ $pdfLogoSize }},
            marginTop:        '{{ $pdfMarginTop }}',
            marginRight:      '{{ $pdfMarginRight }}',
            marginBottom:     '{{ $pdfMarginBottom }}',
            marginLeft:       '{{ $pdfMarginLeft }}',
            marginPreset:     '{{ $pdfPageMargin }}',
            hasLogo:          {{ $hasLogo ? 'true' : 'false' }},
            logoUrl:          '{{ $logoUrl }}',
            paperSize:        '{{ $pdfPaperSize }}',

            {{-- Compute effective margin for preview from preset --}}
            get marginPx() {
                if (this.marginTop || this.marginRight || this.marginBottom || this.marginLeft) {
                    const cm = (v, fallback) => v ? parseFloat(v) * 14 : fallback;
                    return {
                        top:    cm(this.marginTop, 20) + 'px',
                        right:  cm(this.marginRight, 28) + 'px',
                        bottom: cm(this.marginBottom, 7) + 'px',
                        left:   cm(this.marginLeft, 28) + 'px',
                    };
                }
                const presets = {
                    narrow: { top:'21px', right:'14px', bottom:'14px', left:'14px' },
                    normal: { top:'0px',  right:'28px', bottom:'7px',  left:'28px' },
                    wide:   { top:'56px', right:'49px', bottom:'56px', left:'49px' },
                };
                return presets[this.marginPreset] ?? presets.normal;
            },

            get previewBodyStyle() {
                return 'font-family: ' + this.font + '; font-size: ' + this.fontSize + 'pt; line-height: ' + this.lineHeight + '; color: #000;';
            }
        }"
    >

        {{-- Internal Tab Navigation --}}
        <div class="mb-4">
            <ul class="nav nav-tabs nav-fill" role="tablist">
                <li class="nav-item">
                    <a class="nav-link" :class="activePdfTab === 'layout' ? 'active' : ''" href="#" @click.prevent="activePdfTab = 'layout'">
                        <x-lucide-sliders class="icon me-1" />
                        Tata Letak &amp; Tipografi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="activePdfTab === 'cache' ? 'active' : ''" href="#" @click.prevent="activePdfTab = 'cache'">
                        <x-lucide-hard-drive class="icon me-1" />
                        Kelola Cache PDF
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" :class="activePdfTab === 'modules' ? 'active' : ''" href="#" @click.prevent="activePdfTab = 'modules'">
                        <x-lucide-layout-list class="icon me-1" />
                        Modul &amp; Fitur PDF
                    </a>
                </li>
            </ul>
        </div>

        {{-- ==================== TAB 1: TATA LETAK ==================== --}}
        <div x-show="activePdfTab === 'layout'" x-cloak>
            <div class="row">
                {{-- ---- LEFT COLUMN: Controls ---- --}}
                <div class="col-md-7">

                    {{-- === LOGO & KOP SURAT === --}}
                    <h4 class="card-title mb-3">
                        <x-lucide-image class="icon me-1 text-primary" />
                        Logo &amp; Kop Surat
                    </h4>

                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            {{-- Show Logo toggle --}}
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="pdfShowLogo"
                                        wire:model.live="pdfShowLogo"
                                        x-on:change="showLogo = $event.target.checked">
                                    <label class="form-check-label fw-medium" for="pdfShowLogo">Tampilkan Logo di Kop Surat</label>
                                </div>
                                <small class="text-muted">Sembunyikan logo jika tidak diperlukan (mis. draft internal).</small>
                            </div>

                            {{-- Logo Position --}}
                            <div class="mb-3" x-show="showLogo">
                                <label class="form-label fw-medium mb-2">Posisi Logo di Kop Surat</label>
                                <div class="row g-2">
                                    @foreach([['left','Kiri','align-start-vertical'],['center','Tengah','align-center-vertical'],['right','Kanan','align-end-vertical']] as [$val, $label, $icon])
                                    <div class="col-4">
                                        <label class="w-100 cursor-pointer">
                                            <input type="radio" class="d-none" value="{{ $val }}"
                                                wire:model.live="pdfLogoPosition"
                                                x-on:change="logoPos = '{{ $val }}'">
                                            <div class="card border text-center p-2 py-3"
                                                :class="logoPos === '{{ $val }}' ? 'border-primary bg-primary-lt' : 'border-secondary-subtle'"
                                                style="cursor:pointer; transition: all .15s">
                                                @if($val === 'left')
                                                    <div style="display:flex; align-items:center; gap:4px; justify-content:flex-start; height:32px; padding: 0 4px;">
                                                        <div style="width:18px; height:18px; background:#adb5bd; border-radius:3px; flex-shrink:0;"></div>
                                                        <div style="flex:1; display:flex; flex-direction:column; gap:3px;">
                                                            <div style="height:3px; background:#495057; border-radius:2px;"></div>
                                                            <div style="height:2px; background:#adb5bd; border-radius:2px; width:80%;"></div>
                                                            <div style="height:2px; background:#adb5bd; border-radius:2px; width:65%;"></div>
                                                        </div>
                                                    </div>
                                                @elseif($val === 'center')
                                                    <div style="display:flex; flex-direction:column; align-items:center; height:32px; gap:3px; padding-top:2px;">
                                                        <div style="width:16px; height:16px; background:#adb5bd; border-radius:3px;"></div>
                                                        <div style="height:2px; background:#495057; border-radius:2px; width:90%;"></div>
                                                    </div>
                                                @else
                                                    <div style="display:flex; align-items:center; gap:4px; justify-content:flex-end; height:32px; padding: 0 4px;">
                                                        <div style="flex:1; display:flex; flex-direction:column; gap:3px; align-items:flex-end;">
                                                            <div style="height:3px; background:#495057; border-radius:2px; width:100%;"></div>
                                                            <div style="height:2px; background:#adb5bd; border-radius:2px; width:80%;"></div>
                                                            <div style="height:2px; background:#adb5bd; border-radius:2px; width:65%;"></div>
                                                        </div>
                                                        <div style="width:18px; height:18px; background:#adb5bd; border-radius:3px; flex-shrink:0;"></div>
                                                    </div>
                                                @endif
                                                <div class="small mt-1 fw-medium" :class="logoPos === '{{ $val }}' ? 'text-primary' : 'text-muted'">{{ $label }}</div>
                                            </div>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Logo Size --}}
                            <div class="mb-0" x-show="showLogo">
                                <label class="form-label fw-medium">Ukuran Logo</label>
                                <select class="form-select" wire:model.live="pdfLogoSize"
                                    x-on:change="logoSize = parseInt($event.target.value)">
                                    <option value="70">Kecil (70px)</option>
                                    <option value="90">Sedang Kecil (90px)</option>
                                    <option value="110">Sedang (110px) — Default</option>
                                    <option value="130">Besar (130px)</option>
                                    <option value="150">Sangat Besar (150px)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- === TIPOGRAFI (Family A) === --}}
                    <h4 class="card-title mb-3 mt-2">
                        <x-lucide-type class="icon me-1 text-indigo" />
                        Tipografi — Surat &amp; Proposal (Keluarga A)
                    </h4>

                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Font Family</label>
                                    <select class="form-select" wire:model.live="pdfFontFamily"
                                        x-on:change="font = $event.target.value">
                                        <option value="Times New Roman, Times, serif">Times New Roman — Default (Formal)</option>
                                        <option value="Arial, Helvetica, sans-serif">Arial (Modern Sans-Serif)</option>
                                        <option value="Georgia, serif">Georgia (Klasik Serif)</option>
                                        <option value="Courier New, Courier, monospace">Courier New (Monospace)</option>
                                        <option value="Garamond, serif">Garamond (Elegant Book)</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-medium">Ukuran Kertas <span class="badge bg-green-lt">Baru</span></label>
                                    <select class="form-select" wire:model.live="pdfPaperSize" x-on:change="paperSize = $event.target.value">
                                        <option value="a4">A4 (210 × 297 mm) — Default</option>
                                        <option value="folio">F4 / Folio (215 × 330 mm)</option>
                                        <option value="letter">Letter (215.9 × 279.4 mm)</option>
                                        <option value="legal">Legal (215.9 × 355.6 mm)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Ukuran Font</label>
                                    <select class="form-select" wire:model.live="pdfBodyFontSize"
                                        x-on:change="fontSize = parseInt($event.target.value)">
                                        <option value="8">8 pt</option>
                                        <option value="9">9 pt</option>
                                        <option value="10">10 pt</option>
                                        <option value="11">11 pt — Default</option>
                                        <option value="12">12 pt</option>
                                        <option value="14">14 pt</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Spasi Baris <small class="text-muted">(Line-Height)</small></label>
                                    <select class="form-select" wire:model.live="pdfLineHeight"
                                        x-on:change="lineHeight = $event.target.value">
                                        <option value="1.0">1.0 — Sangat Padat</option>
                                        <option value="1.1">1.1 — Padat (Default)</option>
                                        <option value="1.15">1.15 — Agak Rapat</option>
                                        <option value="1.3">1.3 — Normal</option>
                                        <option value="1.5">1.5 — Longgar</option>
                                        <option value="2.0">2.0 — Ganda (Double Spaced)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Jarak Antar Paragraf</label>
                                    <select class="form-select" wire:model.live="pdfParagraphSpacing"
                                        x-on:change="paraSpacing = parseInt($event.target.value)">
                                        <option value="0">Tidak ada (0px)</option>
                                        <option value="3">Sangat Rapat (3px)</option>
                                        <option value="6">Normal (6px) — Default</option>
                                        <option value="10">Longgar (10px)</option>
                                        <option value="14">Sangat Longgar (14px)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Indentasi Baris Pertama</label>
                                    <select class="form-select" wire:model.live="pdfParagraphIndent"
                                        x-on:change="paraIndent = parseInt($event.target.value)">
                                        <option value="0">Tidak ada — Default</option>
                                        <option value="10">10px (¼ cm)</option>
                                        <option value="20">20px (½ cm)</option>
                                        <option value="30">30px (¾ cm)</option>
                                        <option value="40">40px (1 cm)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="pdfLayoutCompact"
                                            wire:model.live="pdfLayoutCompact">
                                        <label class="form-check-label fw-medium" for="pdfLayoutCompact">Mode Padat (Override semua spasi ke minimum)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- === MARGIN HALAMAN === --}}
                    <h4 class="card-title mb-3 mt-2">
                        <x-lucide-maximize class="icon me-1 text-orange" />
                        Margin Halaman
                    </h4>

                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-medium">Preset Margin</label>
                                <select class="form-select" wire:model.live="pdfPageMargin"
                                    x-on:change="marginPreset = $event.target.value">
                                    <option value="narrow">Sempit — 1.5cm × 1.0cm</option>
                                    <option value="normal">Normal — Default Kanonik</option>
                                    <option value="wide">Lebar — 4.0cm × 3.5cm</option>
                                </select>
                                <small class="text-muted">Preset margin digunakan jika Custom Margin di bawah dikosongkan.</small>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-medium">Custom Margin Per Sisi <span class="badge bg-blue-lt">cm</span></label>
                                <small class="d-block text-muted mb-2">Kosongkan untuk menggunakan preset di atas. Isi salah satu sisi untuk override.</small>
                                <div class="row g-2">
                                    @foreach([['pdfMarginTop','Atas (Top)','marginTop'],['pdfMarginRight','Kanan (Right)','marginRight'],['pdfMarginBottom','Bawah (Bottom)','marginBottom'],['pdfMarginLeft','Kiri (Left)','marginLeft']] as [$prop, $label, $alpine])
                                    <div class="col-6">
                                        <label class="form-label small mb-1">{{ $label }}</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" class="form-control" step="0.1" min="0" max="10"
                                                placeholder="e.g. 2.5"
                                                wire:model.live="{{ $prop }}"
                                                x-on:input="{{ $alpine }} = $event.target.value">
                                            <span class="input-group-text">cm</span>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- === LAPORAN MODUL (Family B) === --}}
                    <h4 class="card-title mb-3 mt-2">
                        <x-lucide-file-text class="icon me-1 text-success" />
                        Tipografi — Laporan Modul (Keluarga B)
                    </h4>

                    <div class="card mb-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Font Family Laporan</label>
                                    <select class="form-select" wire:model.live="pdfReportFontFamily">
                                        <option value="Arial, Helvetica, sans-serif">Arial — Default Laporan</option>
                                        <option value="Times New Roman, Times, serif">Times New Roman</option>
                                        <option value="Georgia, serif">Georgia</option>
                                        <option value="Courier New, Courier, monospace">Courier New</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Ukuran Font Laporan</label>
                                    <select class="form-select" wire:model.live="pdfReportFontSize">
                                        <option value="7">7 pt (Sangat Padat)</option>
                                        <option value="8">8 pt</option>
                                        <option value="9">9 pt — Default</option>
                                        <option value="10">10 pt</option>
                                        <option value="11">11 pt</option>
                                        <option value="12">12 pt</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Spasi Baris Laporan</label>
                                    <select class="form-select" wire:model.live="pdfReportLineHeight">
                                        <option value="1.0">1.0 — Sangat Padat</option>
                                        <option value="1.1">1.1 — Padat (Default)</option>
                                        <option value="1.15">1.15</option>
                                        <option value="1.3">1.3 — Normal</option>
                                        <option value="1.5">1.5 — Longgar</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- === Tanda Tangan reference === --}}
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body d-flex align-items-center">
                            <span class="avatar bg-indigo-lt me-3">
                                <x-lucide-settings class="icon" />
                            </span>
                            <div>
                                <div class="fw-medium">Pengaturan Alur Tanda Tangan &amp; Bypass</div>
                                <div class="text-muted small">
                                    Dikonfigurasi melalui
                                    <a href="#" wire:click.prevent="$parent.setActiveTab('feature-flags')" class="fw-semibold text-decoration-underline">
                                        Tab Feature Flags <x-lucide-arrow-right class="icon icon-inline ms-1" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---- RIGHT COLUMN: REAL Live Preview ---- --}}
                <div class="col-md-5" x-data="{
                    previewModule: 'dummy',
                    refreshKey: Date.now(),
                    refreshPreview() {
                        this.refreshKey = Date.now();
                    }
                }" x-on:settings-updated.window="refreshPreview()">
                    <div class="sticky-top" style="top: 1.5rem; z-index: 100;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="card-title mb-0">
                                <x-lucide-monitor class="icon me-1 text-primary" />
                                Pratinjau Instan (Real PDF)
                            </h4>
                            <button class="btn btn-sm btn-outline-primary" @click="refreshPreview()">
                                <x-lucide-refresh-cw class="icon me-1" /> Segarkan
                            </button>
                        </div>
                        <p class="text-muted small mb-2">Pratinjau ini dirender menggunakan <strong>mesin PDF asli (DomPDF)</strong>. Data ditarik sinkron dari sistem backend.</p>

                        <div class="mb-3">
                            <select class="form-select form-select-sm border-primary text-primary fw-bold" x-model="previewModule" @change="refreshPreview()">
                                <option value="dummy">-- Pilih Modul Pratinjau (Default) --</option>
                                <option value="pdf.letters.surat-tugas">1. Surat Tugas</option>
                                <option value="pdf.letters.surat-keterangan">2. Surat Keterangan</option>
                                <option value="pdf.letters.surat-permohonan-izin">3. Surat Permohonan Izin</option>
                                <option value="pdf.proposal-export">4. Ekspor Proposal</option>
                                <option value="pdf.report-export">5. Laporan Kemajuan Proposal</option>
                                <option value="pdf.daily-notes">6. Logbook Harian</option>
                                <option value="pdf.review-evaluation">7. Evaluasi Reviewer</option>
                                <option value="reports.iku-report-pdf">8. Laporan IKU</option>
                                <option value="reports.research-pdf">9. Laporan Penelitian</option>
                                <option value="reports.community-service-pdf">10. Laporan Pengabdian</option>
                                <option value="reports.output-reports-pdf">11. Laporan Output</option>
                                <option value="reports.partner-collaboration-pdf">12. Laporan Kerjasama Mitra</option>
                                <option value="reports.monev-ba-pdf">13. Laporan Monev Berita Acara</option>
                                <option value="reports.monev-pdf">14. Laporan Monev</option>
                                <option value="reports.reviewer-report-pdf">15. Laporan Reviewer</option>
                            </select>
                        </div>

                        <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 8px; height: 700px;">
                            <iframe :src="`{{ route('settings.pdf-preview') }}?module=${previewModule}&t=${refreshKey}`" width="100%" height="100%" frameborder="0" style="background:#525659;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== TAB 2: KELOLA CACHE ==================== --}}
        <div x-show="activePdfTab === 'cache'" x-cloak>
            <div class="mb-3">
                <h4 class="card-title">
                    <x-lucide-hard-drive class="icon me-1 text-primary" />
                    Kelola Cache PDF
                </h4>
                <p class="text-muted">Cache PDF dibuat otomatis saat pertama kali dokumen diunduh dan disimpan sementara di server untuk mempercepat unduhan berikutnya. Membersihkan cache tidak menghapus dokumen — PDF akan di-generate ulang otomatis saat diunduh lagi.</p>
            </div>

            <div class="row g-3 mb-4">
                @foreach(['proposals' => ['Proposal', 'file-text', 'blue'], 'reports' => ['Laporan', 'bar-chart-2', 'green'], 'reviewer' => ['Reviewer', 'users', 'orange']] as $type => [$label, $icon, $color])
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar bg-{{ $color }}-lt me-3">
                                    <x-dynamic-component :component="'lucide-'.$icon" class="icon text-{{ $color }}" />
                                </span>
                                <div>
                                    <div class="fw-medium">Cache {{ $label }}</div>
                                    <div class="text-muted small">pdf_cache/{{ $type }}</div>
                                </div>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="h3 mb-0 fw-bold text-{{ $color }}">{{ $cacheStats[$type]['count'] }}</div>
                                    <div class="text-muted small">File PDF</div>
                                </div>
                                <div class="col-6">
                                    <div class="h3 mb-0 fw-bold text-{{ $color }}">{{ $cacheStats[$type]['size'] }}</div>
                                    <div class="text-muted small">Ukuran</div>
                                </div>
                            </div>
                            <button
                                wire:click="clearPdfCache('{{ $type }}')"
                                wire:confirm="Hapus {{ $cacheStats[$type]['count'] }} file cache {{ $label }}? PDF akan di-generate ulang saat diunduh."
                                class="btn btn-outline-{{ $color }} btn-sm w-100"
                                @if($cacheStats[$type]['count'] === 0) disabled @endif
                            >
                                <x-lucide-trash-2 class="icon icon-sm me-1" />
                                Bersihkan Cache {{ $label }}
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @php
                $totalFiles = array_sum(array_column($cacheStats, 'count'));
                $totalBytes = array_sum(array_column($cacheStats, 'bytes'));
                $totalSize = $totalBytes >= 1_048_576
                    ? round($totalBytes / 1_048_576, 2).' MB'
                    : ($totalBytes >= 1024 ? round($totalBytes / 1024, 1).' KB' : $totalBytes.' B');
            @endphp
            <div class="card border-danger-subtle border mb-3">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-medium text-danger">Bersihkan Semua Cache PDF</div>
                        <div class="text-muted small">Total: <strong>{{ $totalFiles }} file</strong> • <strong>{{ $totalFiles > 0 ? (new App\Livewire\Settings\PdfExportSettings)->formatBytes($totalBytes) : '0 B' }}</strong></div>
                    </div>
                    <button
                        wire:click="clearPdfCache('all')"
                        wire:confirm="Hapus SEMUA {{ $totalFiles }} file cache PDF? Semua PDF akan di-generate ulang saat diunduh pertama kali."
                        class="btn btn-danger"
                        @if($totalFiles === 0) disabled @endif
                    >
                        <x-lucide-trash class="icon me-1" />
                        Bersihkan Semua ({{ $totalFiles }} file)
                    </button>
                </div>
            </div>

            <div class="alert alert-info">
                <x-lucide-info class="icon me-2" />
                <strong>Info:</strong> Cache PDF secara otomatis di-invalidasi saat dokumen sumbernya diperbarui (berdasarkan timestamp <code>updated_at</code>). Membersihkan cache manual hanya perlu dilakukan jika ada perubahan pengaturan tampilan PDF atau untuk membebaskan storage.
            </div>
        </div>

        {{-- ==================== TAB 3: MODUL & FITUR ==================== --}}
        <div x-show="activePdfTab === 'modules'" x-cloak>
            <div class="mb-3">
                <h4 class="card-title">
                    <x-lucide-layout-list class="icon me-1 text-primary" />
                    Daftar Modul &amp; Fitur Penghasil PDF
                </h4>
                <p class="text-muted">Semua 16 titik ekspor PDF di sistem ini menggunakan pengaturan global dari halaman ini. Pengaturan Keluarga A berlaku untuk modul surat/proposal, Keluarga B untuk laporan.</p>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th>Nama Modul / Fitur</th>
                            <th>Template View</th>
                            <th width="12%">Keluarga</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['surat-tugas', 'Surat Tugas','pdf/letters/surat-tugas','A'],
                            ['surat-keterangan', 'Surat Keterangan','pdf/letters/surat-keterangan','A'],
                            ['surat-izin', 'Surat Permohonan Izin','pdf/letters/surat-permohonan-izin','A'],
                            ['proposal-export', 'Ekspor Proposal','pdf/proposal-export','A'],
                            ['laporan-kemajuan', 'Laporan Kemajuan Proposal','pdf/report-export','A'],
                            ['logbook', 'Logbook Harian','pdf/daily-notes','A'],
                            ['evaluasi-reviewer', 'Evaluasi Reviewer','pdf/review-evaluation','A'],
                            ['iku', 'Laporan IKU','reports/iku-report-pdf','B'],
                            ['penelitian', 'Laporan Penelitian','reports/research-pdf','B'],
                            ['pengabdian', 'Laporan Pengabdian','reports/community-service-pdf','B'],
                            ['output', 'Laporan Output','reports/output-reports-pdf','B'],
                            ['mitra', 'Laporan Kerjasama Mitra','reports/partner-collaboration-pdf','B'],
                            ['monev-ba', 'Laporan Monev Berita Acara','reports/monev-ba-pdf','B'],
                            ['monev', 'Laporan Monev','reports/monev-pdf','B'],
                            ['reviewer', 'Laporan Reviewer','reports/reviewer-report-pdf','B'],
                        ] as $index => [$key, $name, $template, $family])
                        <tr>
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td class="fw-medium">{{ $name }}</td>
                            <td><code style="font-size:11px;">{{ $template }}</code></td>
                            <td>
                                @if($family === 'A')
                                    <span class="badge bg-blue-lt text-blue">Keluarga A</span>
                                @else
                                    <span class="badge bg-green-lt text-green">Keluarga B</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openContentEditor('{{ $key }}', '{{ $name }}')">
                                    <x-lucide-edit-3 class="icon icon-sm me-1" /> Edit Konten
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="card bg-blue-lt border-0">
                        <div class="card-body">
                            <h5 class="card-title text-blue mb-1">Keluarga A — 8 Modul</h5>
                            <p class="text-muted small mb-0">Surat resmi, proposal, laporan kemajuan, logbook, reviewer. Menggunakan font <strong>Times New Roman 11pt</strong> sebagai default. Kop surat menggunakan partial <code>header.blade.php</code> yang dikontrol pengaturan posisi logo.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-green-lt border-0">
                        <div class="card-body">
                            <h5 class="card-title text-green mb-1">Keluarga B — 8 Modul</h5>
                            <p class="text-muted small mb-0">Laporan rekap IKU, Monev, PKM, Output, Mitra, Reviewer. Menggunakan font <strong>Arial 9pt</strong> sebagai default. Layout lebih padat untuk efisiensi tabel data. Dikontrol pengaturan Laporan Modul.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== MODAL EDITOR KONTEN ==================== --}}
        @if($contentModalOpen)
            <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.5);">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <x-lucide-edit-3 class="icon me-1 text-primary" />
                                Edit Teks Pengantar: {{ $editingModuleName }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="closeContentEditor"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info bg-info-lt">
                                <x-lucide-info class="icon me-2" />
                                <strong>Penting:</strong> Anda mengedit <strong>teks statis</strong> dari modul PDF ini. Tabel data, perhitungan, dan tanda tangan akan tetap dirender secara otomatis oleh sistem (tidak bisa diedit manual) demi menjaga keamanan dan validitas dokumen.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Teks Pengantar (Intro)</label>
                                <textarea class="form-control" wire:model="editingContentIntro" rows="4" placeholder="Kosongkan untuk menggunakan teks bawaan (default) sistem."></textarea>
                                <small class="text-muted">Paragraf yang muncul di bagian atas dokumen sebelum tabel data utama.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Teks Penutup (Outro)</label>
                                <textarea class="form-control" wire:model="editingContentOutro" rows="4" placeholder="Kosongkan untuk menggunakan teks bawaan (default) sistem."></textarea>
                                <small class="text-muted">Paragraf yang muncul di bagian bawah dokumen sebelum area tanda tangan.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn me-auto" wire:click="closeContentEditor">Batal</button>
                            <button type="button" class="btn btn-primary" wire:click="saveContentEditor">
                                <x-lucide-save class="icon me-1" /> Simpan Konten
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
