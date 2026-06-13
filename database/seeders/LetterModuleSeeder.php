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
        Setting::set('module_persuratan_active', false, 'boolean');
        Setting::set('surat_signature_mode', 'tte', 'string'); // Default TTE/Barcode

        // 2. Initial Letter Types
        $types = [
            [
                'code' => 'ST',
                'name' => 'Surat Tugas',
                'description' => 'Surat tugas untuk pelaksanaan kegiatan penelitian atau PKM',
                'category' => 'pelaksanaan',
                'numbering_format' => '{NOMOR}/ST/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => 'pdf.letters.surat-tugas',
                'is_uploadable' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SP',
                'name' => 'Surat Permohonan Izin',
                'description' => 'Surat permohonan izin kegiatan penelitian atau PKM',
                'category' => 'persiapan',
                'numbering_format' => '{NOMOR}/SP/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => 'pdf.letters.surat-permohonan-izin',
                'is_uploadable' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SKET',
                'name' => 'Surat Keterangan Selesai',
                'description' => 'Surat keterangan bahwa kegiatan telah selesai dilaksanakan',
                'category' => 'pelaporan',
                'numbering_format' => '{NOMOR}/SKET/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
                'template_view' => 'pdf.letters.surat-keterangan',
                'is_uploadable' => false,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LetterType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
