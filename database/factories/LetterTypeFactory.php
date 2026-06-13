<?php

namespace Database\Factories;

use App\Models\LetterType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LetterTypeFactory extends Factory
{
    protected $model = LetterType::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->lexify('???'),
            'name' => $this->faker->sentence(2),
            'category' => $this->faker->randomElement(['persiapan', 'etik', 'pelaksanaan', 'pelaporan']),
            'numbering_format' => '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
            'template_view' => 'pdf.letters.surat-tugas',
            'is_uploadable' => false,
        ];
    }
}
