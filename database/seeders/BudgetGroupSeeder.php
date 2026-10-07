<?php

namespace Database\Seeders;

use App\Models\BudgetGroup;
use Illuminate\Database\Seeder;

class BudgetGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Based on BIMA Kemdiktisaintek RAB Components 2026
     * Reference: Buku Panduan Penelitian dan Pengabdian kepada Masyarakat 2026
     * Source: Kemdiktisaintek (Kementerian Pendidikan Tinggi, Sains, dan Teknologi)
     *
     * Budget Percentage Limits (Total must = 100%):
     * - Honor/Upah: Max 10%
     * - Teknologi & Inovasi (Bahan/Peralatan): Min 50%
     * - Pelatihan: Max 20%
     * - Perjalanan: Max 15%
     * - Lainnya: Max 5%
     */
    public function run(): void
    {
        $groups = [
            [
                'code' => 'HONOR',
                'name' => 'Upah dan Jasa (Honor)',
                'description' => 'Honorarium peneliti, asisten, operator, tenaga ahli, dan jasa pihak ketiga',
                'percentage' => 10.00, // Maksimal 10% dari total anggaran
                'percentage_type' => 'max',
            ],
            [
                'code' => 'TEKNOLOGI',
                'name' => 'Teknologi dan Inovasi',
                'description' => 'Bahan habis pakai, bahan penelitian, alat laboratorium, peralatan pendukung, dan pengembangan teknologi',
                'percentage' => 50.00, // Minimal 50% dari total anggaran (dialokasikan sebagai baseline)
                'percentage_type' => 'min',
            ],
            [
                'code' => 'PELATIHAN',
                'name' => 'Biaya Pelatihan',
                'description' => 'Pelatihan, workshop, penyuluhan, atau capacity building dalam rangka program penelitian/pengabdian',
                'percentage' => 20.00, // Maksimal 20%
                'percentage_type' => 'max',
            ],
            [
                'code' => 'PERJALANAN',
                'name' => 'Biaya Perjalanan',
                'description' => 'Transportasi, akomodasi, dan konsumsi untuk pelaksanaan kegiatan di lokasi penelitian/pengabdian dan koordinasi dengan mitra',
                'percentage' => 15.00, // Maksimal 15%
                'percentage_type' => 'max',
            ],
            [
                'code' => 'LAINNYA',
                'name' => 'Biaya Lainnya',
                'description' => 'Publikasi hasil, seminar, pelaporan, dokumentasi, dan kebutuhan administrasi penunjang lainnya',
                'percentage' => 5.00, // Maksimal 5%
                'percentage_type' => 'max',
            ],
        ];

        foreach ($groups as $group) {
            BudgetGroup::updateOrCreate(
                ['code' => $group['code']],
                $group
            );
        }
    }
}
