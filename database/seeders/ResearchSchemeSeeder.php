<?php

namespace Database\Seeders;

use App\Models\ResearchScheme;
use Illuminate\Database\Seeder;

class ResearchSchemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Based on BIMA Kemdiktisaintek 2026
     * Reference: Buku Panduan Penelitian dan Pengabdian kepada Masyarakat 2026
     */
    public function run(): void
    {
        $schemes = [
            // PENELITIAN DASAR — Reguler
            [
                'name' => 'Penelitian Dosen Pemula (PDP)',
                'strata' => 'Reguler',
                'description' => 'Membina dan mengarahkan dosen pemula dalam penelitian',
            ],
            [
                'name' => 'Penelitian Pascasarjana - Tesis Magister (PTM)',
                'strata' => 'Reguler',
                'description' => 'Meningkatkan produktivitas mahasiswa S2 melalui penelitian tesis',
            ],
            [
                'name' => 'Penelitian Pascasarjana - Disertasi Doktor (PDD)',
                'strata' => 'Reguler',
                'description' => 'Meningkatkan produktivitas mahasiswa S3 melalui penelitian disertasi',
            ],
            [
                'name' => 'Penelitian Fundamental',
                'strata' => 'Reguler',
                'description' => 'Penelitian untuk pengembangan ilmu pengetahuan dan teknologi',
            ],
            [
                'name' => 'Penelitian Kerja Sama antar Perguruan Tinggi (PKPT)',
                'strata' => 'Kerja Sama Antar PT',
                'description' => 'Kerja sama penelitian antara perguruan tinggi pengirim dan mitra',
            ],

            // PENELITIAN TERAPAN — Reguler / Kolaborasi Internal
            [
                'name' => 'Penelitian Terapan - Luaran Prototipe',
                'strata' => 'Reguler',
                'description' => 'Penelitian yang menghasilkan prototipe teknologi atau produk',
            ],
            [
                'name' => 'Penelitian Terapan - Luaran Model',
                'strata' => 'Reguler',
                'description' => 'Penelitian yang menghasilkan model, kebijakan, atau karya seni',
            ],

            // PENGABDIAN KEPADA MASYARAKAT — PKM-Reguler
            [
                'name' => 'Pemberdayaan Berbasis Masyarakat (PBM)',
                'strata' => 'PKM-Reguler',
                'description' => 'Pemberdayaan kelompok masyarakat dalam pemecahan masalah',
            ],
            [
                'name' => 'Pemberdayaan Berbasis Kewirausahaan (PBK)',
                'strata' => 'PKM-Reguler',
                'description' => 'Pemberdayaan kewirausahaan melalui UPUD atau kelompok usaha',
            ],
            [
                'name' => 'Pemberdayaan Berbasis Wilayah - Pemberdayaan Desa Binaan (PDB)',
                'strata' => 'PKM-Reguler',
                'description' => 'Pemberdayaan desa binaan secara berkelanjutan',
            ],
            [
                'name' => 'Pemberdayaan Berbasis Wilayah - Pemberdayaan Wilayah (PW)',
                'strata' => 'PKM-Reguler',
                'description' => 'Pemberdayaan wilayah melalui aplikasi ipteks',
            ],

            // INTERNAL SCHEMES
            [
                'name' => 'Penelitian Internal ITSNU',
                'strata' => 'Reguler',
                'description' => 'Penelitian yang didanai secara internal oleh ITSNU',
            ],
            [
                'name' => 'Pengabdian Internal ITSNU',
                'strata' => 'PKM-Reguler',
                'description' => 'Pengabdian yang didanai secara internal oleh ITSNU',
            ],
        ];

        foreach ($schemes as $scheme) {
            ResearchScheme::updateOrCreate(
                ['name' => $scheme['name']],
                $scheme
            );
        }
    }
}
