<?php

return [
    'list' => [
        // Surat (Family A)
        [
            'key' => 'surat-tugas',
            'name' => 'Surat Tugas',
            'family' => 'A',
            'view_type' => 'letter',
            'template' => 'pdf.letters.surat-tugas',
        ],
        [
            'key' => 'surat-izin',
            'name' => 'Surat Permohonan Izin',
            'family' => 'A',
            'view_type' => 'letter',
            'template' => 'pdf.letters.surat-permohonan-izin',
        ],
        [
            'key' => 'surat-keterangan',
            'name' => 'Surat Keterangan',
            'family' => 'A',
            'view_type' => 'letter',
            'template' => 'pdf.letters.surat-keterangan',
        ],

        // Laporan Akhir & Proposal (Family C)
        [
            'key' => 'proposal-export',
            'name' => 'Ekspor Proposal',
            'family' => 'C',
            'view_type' => 'letter',
            'template' => 'pdf.proposal-export',
        ],
        [
            'key' => 'laporan-akhir',
            'name' => 'Laporan Akhir',
            'family' => 'C',
            'view_type' => 'letter',
            'template' => 'pdf.report-export',
        ],
        [
            'key' => 'logbook',
            'name' => 'Logbook Harian',
            'family' => 'A',
            'view_type' => 'letter',
            'template' => 'pdf.daily-notes',
        ],
        [
            'key' => 'evaluasi-reviewer',
            'name' => 'Evaluasi Reviewer',
            'family' => 'A',
            'view_type' => 'letter',
            'template' => 'pdf.review-evaluation',
        ],

        // Laporan LPPM ke Rektor (Family B)
        [
            'key' => 'iku',
            'name' => 'Laporan IKU',
            'family' => 'B',
            'view_type' => 'report',
            'template' => 'reports.iku-report-pdf',
        ],
        [
            'key' => 'penelitian',
            'name' => 'Laporan Penelitian',
            'family' => 'B',
            'view_type' => 'report',
            'template' => 'reports.research-pdf',
        ],
        [
            'key' => 'pengabdian',
            'name' => 'Laporan Pengabdian',
            'family' => 'B',
            'view_type' => 'report',
            'template' => 'reports.community-service-pdf',
        ],
        [
            'key' => 'output',
            'name' => 'Laporan Output',
            'family' => 'B',
            'view_type' => 'report',
            'template' => 'reports.output-reports-pdf',
        ],
        [
            'key' => 'mitra',
            'name' => 'Laporan Kerjasama Mitra',
            'family' => 'B',
            'view_type' => 'report',
            'template' => 'reports.partner-collaboration-pdf',
        ],
        [
            'key' => 'monev-ba',
            'name' => 'Laporan Monev (BA)',
            'family' => 'B',
            'view_type' => 'report_ba',
            'template' => 'reports.monev-ba-pdf',
        ],
        [
            'key' => 'monev',
            'name' => 'Laporan Monev',
            'family' => 'B',
            'view_type' => 'report',
            'template' => 'reports.monev-pdf',
        ],
        [
            'key' => 'reviewer',
            'name' => 'Laporan Reviewer',
            'family' => 'B',
            'view_type' => 'report_compact',
            'template' => 'reports.reviewer-report-pdf',
        ],
    ],

    'families' => [
        'A' => [
            'label' => 'Surat Administratif',
            'default_font' => "'Times New Roman', Times, serif",
            'default_size' => 11,
        ],
        'B' => [
            'label' => 'Laporan Modul',
            'default_font' => 'Arial, Helvetica, sans-serif',
            'default_size' => 9,
        ],
        'C' => [
            'label' => 'Dokumen Usulan & Hasil',
            'default_font' => "'Times New Roman', Times, serif",
            'default_size' => 11,
        ],
    ],
];
