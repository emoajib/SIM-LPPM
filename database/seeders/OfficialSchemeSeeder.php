<?php

namespace Database\Seeders;

use App\Models\CommunityServiceScheme;
use App\Models\ResearchScheme;
use Illuminate\Database\Seeder;

class OfficialSchemeSeeder extends Seeder
{
    public function run(): void
    {
        // Research Schemes
        $researchSchemes = [
            [
                'name' => 'Skema 1: Reguler',
                'strata' => 'Dasar',
            ],
            [
                'name' => 'Skema 2: Kolaborasi Internal',
                'strata' => 'Dasar',
            ],
            [
                'name' => 'Skema 3: Kolaborasi Eksternal',
                'strata' => 'Dasar',
            ],
        ];

        foreach ($researchSchemes as $scheme) {
            ResearchScheme::updateOrCreate(
                ['name' => $scheme['name']],
                $scheme
            );
        }

        // Community Service Schemes
        $csSchemes = [
            [
                'name' => 'Skema 1: Reguler',
                'strata' => 'PKM-Reguler',
            ],
            [
                'name' => 'Skema 2: Kolaborasi Internal',
                'strata' => 'PKM-KI',
            ],
            [
                'name' => 'Skema 3: Kolaborasi Eksternal',
                'strata' => 'PKM-KE',
            ],
        ];

        foreach ($csSchemes as $scheme) {
            CommunityServiceScheme::updateOrCreate(
                ['name' => $scheme['name']],
                $scheme
            );
        }
    }
}
