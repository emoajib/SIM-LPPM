<div>
    {{-- Vetted by AI - Manual Review Required by Senior Engineer/Manager --}}
    <div class="row">
        <div class="col-md-7">
            <h3 class="card-title mb-3">
                <x-lucide-printer class="icon me-1 text-primary" />
                Pengaturan PDF & Tata Letak Surat/Proposal (Keluarga A)
            </h3>
            <p class="text-secondary mb-3">
                Ubah gaya global untuk dokumen surat-surat resmi, ekspor proposal, logbook, dan evaluasi reviewer.
            </p>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-medium">Font Family</label>
                        <select class="form-select" wire:model.live="pdfFontFamily">
                            <option value="Times New Roman, Times, serif">Times New Roman (Kanonik)</option>
                            <option value="Arial, Helvetica, sans-serif">Arial (Modern Sans-Serif)</option>
                            <option value="Georgia, serif">Georgia (Klasik Serif)</option>
                            <option value="Courier New, Courier, monospace">Courier New (Monospace)</option>
                            <option value="Garamond, serif">Garamond (Elegant Book Serif)</option>
                        </select>
                        <small class="text-muted">Pilihan huruf yang digunakan pada teks utama surat dan usulan.</small>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label font-weight-medium">Ukuran Teks Utama (Font Size)</label>
                            <select class="form-select" wire:model.live="pdfBodyFontSize">
                                <option value="8">8 pt</option>
                                <option value="9">9 pt</option>
                                <option value="10">10 pt</option>
                                <option value="11">11 pt (Default)</option>
                                <option value="12">12 pt</option>
                                <option value="14">14 pt</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label font-weight-medium">Margin Halaman</label>
                            <select class="form-select" wire:model.live="pdfPageMargin">
                                <option value="narrow">Narrow (1.5cm x 1.0cm)</option>
                                <option value="normal">Normal (Kanonik)</option>
                                <option value="wide">Wide (4.0cm x 3.5cm)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="pdfLayoutCompact" wire:model.live="pdfLayoutCompact">
                            <label class="form-check-label font-weight-medium" for="pdfLayoutCompact">Mode Padat (Compact Spacing)</label>
                        </div>
                        <small class="text-muted d-block mt-1">Mengaktifkan line-height rapat untuk menghemat ruang halaman.</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="pdfShowLogo" wire:model.live="pdfShowLogo">
                            <label class="form-check-label font-weight-medium" for="pdfShowLogo">Tampilkan Logo Kop & Cover</label>
                        </div>
                        <small class="text-muted d-block mt-1">Sembunyikan logo universitas pada header surat dan cover laporan jika tidak diperlukan.</small>
                    </div>
                </div>
            </div>

            <h3 class="card-title mb-3 mt-4">
                <x-lucide-file-text class="icon me-1 text-success" />
                Pengaturan PDF Laporan Modul (Keluarga B)
            </h3>
            <p class="text-secondary mb-3">
                Ubah gaya global khusus untuk ekspor Laporan IKU, Monev, PKM, dan output rekapitulasi data.
            </p>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-medium">Font Family Laporan</label>
                        <select class="form-select" wire:model.live="pdfReportFontFamily">
                            <option value="Arial, Helvetica, sans-serif">Arial (Default)</option>
                            <option value="Times New Roman, Times, serif">Times New Roman</option>
                            <option value="Georgia, serif">Georgia</option>
                            <option value="Courier New, Courier, monospace">Courier New</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-medium">Ukuran Teks Utama (Font Size Laporan)</label>
                        <select class="form-select" wire:model.live="pdfReportFontSize">
                            <option value="7">7 pt (Sangat Padat)</option>
                            <option value="8">8 pt</option>
                            <option value="9">9 pt (Default)</option>
                            <option value="10">10 pt</option>
                            <option value="11">11 pt</option>
                            <option value="12">12 pt</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card bg-light border-0">
                <div class="card-body d-flex align-items-center">
                    <span class="avatar bg-indigo-lt me-3">
                        <x-lucide-settings class="icon" />
                    </span>
                    <div>
                        <div class="font-weight-medium">Pengaturan Alur Tanda Tangan & Bypass</div>
                        <div class="text-muted small">
                            Persetujuan manual, tanda tangan digital (TTE), dan bypass cetak basah dikonfigurasi melalui 
                            <a href="#" wire:click.prevent="$parent.setActiveTab('feature-flags')" class="font-weight-semibold text-underline">Tab Feature Flags <x-lucide-arrow-right class="icon icon-inline ms-1" /></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="sticky-top" style="top: 1.5rem; z-index: 100;">
                <h3 class="card-title mb-3">
                    <x-lucide-align-justify class="icon me-1 text-warning" />
                    Pratinjau Instan (Live Preview)
                </h3>
                <p class="text-secondary mb-3">
                    Ilustrasi tampilan dokumen secara real-time saat Anda mengubah pengaturan di samping.
                </p>

                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-purple-lt">Pratinjau Tampilan Surat / Proposal</span>
                            <span class="text-muted small">Ukuran Kertas A4</span>
                        </div>
                    </div>
                    <div class="card-body bg-dark-lt p-4 d-flex justify-content-center">
                        <div class="bg-white shadow-lg p-4 w-100" style="
                            font-family: {{ $pdfFontFamily }};
                            font-size: {{ $pdfBodyFontSize }}pt;
                            line-height: {{ $pdfLayoutCompact ? '1.0' : '1.3' }};
                            color: #000;
                            min-height: 350px;
                            transition: all 0.2s ease-in-out;
                            box-sizing: border-box;
                        ">
                            @if($pdfShowLogo)
                                <div class="text-center mb-3">
                                    <div class="d-inline-flex align-items-center justify-content-center bg-light border rounded" style="width: 55px; height: 55px;">
                                        <x-lucide-image class="text-secondary" style="width: 28px; height: 28px;" />
                                    </div>
                                    <div style="font-size: 8pt; font-weight: bold; margin-top: 5px; text-transform: uppercase;">ITSNU Pekalongan</div>
                                    <div style="font-size: 6pt; color: #666; border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 2px;">
                                        LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT
                                    </div>
                                </div>
                            @else
                                <div class="text-center mb-3 pt-2">
                                    <div style="font-size: 8pt; font-weight: bold; text-transform: uppercase;">ITSNU Pekalongan</div>
                                    <div style="font-size: 6pt; color: #666; border-bottom: 2px solid #000; padding-bottom: 5px; margin-top: 2px;">
                                        LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT
                                    </div>
                                </div>
                            @endif

                            <div class="text-center font-weight-bold my-2" style="font-size: 1.1em; text-decoration: underline;">
                                SURAT KETERANGAN PENELITIAN
                            </div>
                            <div class="text-center text-muted mb-3" style="font-size: 0.85em; margin-top: -5px;">
                                Nomor: 005/LPPM/ITSNU.Pkl/VI/2026
                            </div>

                            <p style="text-indent: 20px; text-align: justify; margin-bottom: 8px;">
                                Yang bertanda tangan di bawah ini, Kepala LPPM ITSNU Pekalongan menerangkan bahwa Dosen pengusul dengan identitas tersebut di bawah ini telah menyelesaikan usulan penelitian internal:
                            </p>

                            <table style="width: 100%; font-size: 0.9em; margin: 8px 0; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 30%; font-weight: bold; vertical-align: top; padding: 2px 0;">Nama Dosen</td>
                                    <td style="width: 5%; vertical-align: top; padding: 2px 0;">:</td>
                                    <td style="vertical-align: top; padding: 2px 0;">Dr. Ahmad Mansur, M.Kom.</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; vertical-align: top; padding: 2px 0;">NIDN</td>
                                    <td style="vertical-align: top; padding: 2px 0;">:</td>
                                    <td style="vertical-align: top; padding: 2px 0;">0612345678</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; vertical-align: top; padding: 2px 0;">Skema Usulan</td>
                                    <td style="vertical-align: top; padding: 2px 0;">:</td>
                                    <td style="vertical-align: top; padding: 2px 0;">Penelitian Dosen Pemula (PDP)</td>
                                </tr>
                            </table>

                            <p style="text-align: justify; margin-top: 8px; margin-bottom: 15px;">
                                Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.
                            </p>

                            <div style="width: 50%; float: right; font-size: 0.9em; text-align: left; margin-top: 10px;">
                                Pekalongan, 15 Juni 2026<br>
                                Kepala LPPM,<br>
                                <div style="height: 40px; margin: 5px 0;">
                                    <span class="badge bg-green-lt py-1 px-2" style="font-size: 6pt; letter-spacing: 0.5px;">✓ VERIFIED BY LPPM</span>
                                </div>
                                <strong><u>Aria Mulyapradana, M.A.</u></strong><br>
                                NPP. 0612118401
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light py-2 px-3 text-secondary" style="font-size: 8.5pt;">
                        <x-lucide-font class="icon icon-inline me-1 text-muted" /> Font Aktif: <strong class="text-dark">{{ explode(',', $pdfFontFamily)[0] }}</strong> | Ukuran Teks: <strong class="text-dark">{{ $pdfBodyFontSize }} pt</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
