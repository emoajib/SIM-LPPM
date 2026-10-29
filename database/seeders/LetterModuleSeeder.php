<?php

namespace Database\Seeders;

use App\Models\LetterType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class LetterModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Feature Flag & Settings
        Setting::set('module_persuratan_active', true, 'boolean');
        Setting::set('surat_signature_mode', 'tte', 'string'); // Default TTE/Barcode

        // 2. Kumpulan Jenis Surat Dinas Berdasarkan 4 Tahapan
        $types = [
            // Tahap Persiapan & Pengajuan (category: persiapan)
            [
                'code' => 'SPF',
                'name' => 'Surat Pengantar dari Fakultas',
                'description' => 'Diajukan oleh dosen ke LPPM melalui dekan.',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SPF/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SP',
                'name' => 'Surat Permohonan Izin Penelitian/Pengabdian',
                'description' => 'Diterbitkan LPPM untuk meminta izin ke instansi/mitra.',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SP/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => 'pdf.letters.surat-permohonan-izin',
                'is_uploadable' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SPBP',
                'name' => 'Surat Pernyataan Bebas Plagiat (Originalitas)',
                'description' => 'Menjamin keaslian karya ilmiah/proposal.',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SPBP/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SPKP',
                'name' => 'Surat Pernyataan Keaslian Penelitian / Karya / Dokumen',
                'description' => 'Untuk proposal, pendaftaran HKI, dan dokumen luaran.',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SPKP/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SPKM',
                'name' => 'Surat Pernyataan Keterlibatan Mahasiswa',
                'description' => 'Mengakui kontribusi dan keterlibatan aktif mahasiswa.',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SPKM/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SPK-KESEDIAAN',
                'name' => 'Surat Pernyataan Kesediaan',
                'description' => 'Menyatakan kesediaan dosen/peneliti mengikuti proses dari awal hingga selesai.',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SPK/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],

            // Tahap Pengajuan Etik (Khusus Penelitian Sensitif) (category: etik)
            [
                'code' => 'SPE',
                'name' => 'Surat Permohonan Etik (Ethical Clearance)',
                'description' => 'Diajukan ke komite etik penelitian.',
                'category' => 'etik',
                'numbering_format' => '{NOMOR}/SPE/KEPK/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SPS',
                'name' => 'Surat Persetujuan Subjek (Informed Consent)',
                'description' => 'Bukti persetujuan partisipan penelitian.',
                'category' => 'etik',
                'numbering_format' => '{NOMOR}/SPS/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CVP',
                'name' => 'Curriculum Vitae (CV) Peneliti Utama dan Anggota',
                'description' => 'Biodata lengkap peneliti untuk kelayakan etik dan portofolio.',
                'category' => 'etik',
                'numbering_format' => '{NOMOR}/CV/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],

            // Tahap Pelaksanaan & Perizinan (category: pelaksanaan)
            [
                'code' => 'ST',
                'name' => 'Surat Tugas Pelaksanaan',
                'description' => 'Legalitas resmi dari LPPM untuk dosen pelaksana penelitian/PKM.',
                'category' => 'pelaksanaan',
                'numbering_format' => '{NOMOR}/ST/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => 'pdf.letters.surat-tugas',
                'is_uploadable' => false,
                'is_active' => true,
            ],
            [
                'code' => 'STM',
                'name' => 'Surat Tugas kepada Mahasiswa',
                'description' => 'Mandat resmi bagi mahasiswa yang terlibat dalam tim kegiatan.',
                'category' => 'pelaksanaan',
                'numbering_format' => '{NOMOR}/STM/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SKR',
                'name' => 'Surat Keputusan / Penunjukan Reviewer',
                'description' => 'Mengukuhkan tim penilai proposal atau monev.',
                'category' => 'pelaksanaan',
                'numbering_format' => '{NOMOR}/SKR/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'SPK-KONTRAK',
                'name' => 'Surat Perjanjian Penugasan / Kontrak Penelitian',
                'description' => 'Mengikat secara hukum kedua belah pihak di atas meterai.',
                'category' => 'pelaksanaan',
                'numbering_format' => '{NOMOR}/KONTRAK/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'LP-PENGESAHAN',
                'name' => 'Lembar Pengesahan Proposal',
                'description' => 'Lembaran pengesahan yang ditandatangani dekan atau kepala LPPM.',
                'category' => 'pelaksanaan',
                'numbering_format' => '{NOMOR}/PENGESAHAN/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],

            // Tahap Pelaporan & Pertanggungjawaban (category: pelaporan)
            [
                'code' => 'SKET',
                'name' => 'Surat Keterangan Penelitian',
                'description' => 'Diterbitkan LPPM sebagai bukti sah telah selesai melaksanakan kegiatan.',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/SKET/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => 'pdf.letters.surat-keterangan',
                'is_uploadable' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SPTB',
                'name' => 'Surat Pernyataan Tanggung Jawab Belanja (SPTB)',
                'description' => 'Untuk pertanggungjawaban keuangan dan realisasi anggaran (bermeterai).',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/SPTB/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BAST-LAP',
                'name' => 'Berita Acara Serah Terima Laporan Kemajuan/Akhir',
                'description' => 'Mencatat serah terima laporan kegiatan kemajuan atau akhir.',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/BAST-LAP/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'BASTB',
                'name' => 'Berita Acara Serah Terima Barang (BASTB)',
                'description' => 'Untuk serah terima barang hasil penelitian/pengabdian.',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/BASTB/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'LAP-AKHIR',
                'name' => 'Laporan Akhir Penelitian',
                'description' => 'Dokumen utama pelaporan hasil kegiatan penelitian/PKM.',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/LAP/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
            [
                'code' => 'LUARAN',
                'name' => 'Luaran Wajib',
                'description' => 'Artikel jurnal, HKI, buku ajar, prosiding yang telah di-accepted.',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/LUARAN/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => null,
                'is_uploadable' => true,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LetterType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
