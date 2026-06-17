<?php

namespace Tests\Unit;

use App\Models\Letter;
use App\Models\LetterType;
use App\Models\Setting;
use App\Services\LetterService;
use App\Services\Validation\LetterValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterServiceTest extends TestCase
{
    use RefreshDatabase;

    private LetterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LetterService(
            new LetterValidationService
        );
    }

    public function test_it_can_convert_all_months_to_roman()
    {
        $this->assertEquals('I', $this->service->getRomanMonth(1));
        $this->assertEquals('II', $this->service->getRomanMonth(2));
        $this->assertEquals('III', $this->service->getRomanMonth(3));
        $this->assertEquals('IV', $this->service->getRomanMonth(4));
        $this->assertEquals('V', $this->service->getRomanMonth(5));
        $this->assertEquals('VI', $this->service->getRomanMonth(6));
        $this->assertEquals('VII', $this->service->getRomanMonth(7));
        $this->assertEquals('VIII', $this->service->getRomanMonth(8));
        $this->assertEquals('IX', $this->service->getRomanMonth(9));
        $this->assertEquals('X', $this->service->getRomanMonth(10));
        $this->assertEquals('XI', $this->service->getRomanMonth(11));
        $this->assertEquals('XII', $this->service->getRomanMonth(12));
    }

    public function test_it_returns_fallback_for_invalid_month()
    {
        $this->assertEquals('13', $this->service->getRomanMonth(13));
        $this->assertEquals('', $this->service->getRomanMonth(0));
    }

    public function test_it_generates_first_letter_number()
    {
        $type = LetterType::factory()->create([
            'code' => 'ST',
            'numbering_format' => '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
        ]);

        $number = $this->service->generateNextNumber($type);

        $year = date('Y');
        $romanMonth = $this->service->getRomanMonth((int) date('n'));
        $this->assertStringStartsWith('001/ST/LPPM/ITSNU.Pkl/'.$romanMonth.'/'.$year, $number);
    }

    public function test_it_increments_sequence_for_subsequent_letters()
    {
        $type = LetterType::factory()->create([
            'code' => 'ST',
            'numbering_format' => '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
        ]);

        Letter::factory()->create([
            'letter_type_id' => $type->id,
            'letter_number' => '001/ST/LPPM/ITSNU.Pkl/VI/2026',
            'status' => 'published',
            'created_at' => now(),
        ]);

        $number = $this->service->generateNextNumber($type);

        $this->assertStringStartsWith('002/', $number);
    }

    public function test_it_resets_sequence_for_new_year()
    {
        $type = LetterType::factory()->create([
            'code' => 'ST',
            'numbering_format' => '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
        ]);

        Letter::factory()->create([
            'letter_type_id' => $type->id,
            'letter_number' => '050/ST/LPPM/ITSNU.Pkl/XII/2025',
            'status' => 'published',
            'created_at' => now()->subYear(),
        ]);

        $number = $this->service->generateNextNumber($type);

        $this->assertStringStartsWith('001/', $number);
    }

    public function test_it_handles_sequential_numbering()
    {
        $type = LetterType::factory()->create([
            'code' => 'ST',
            'numbering_format' => '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{BULAN-ROMAWI}/{TAHUN}',
        ]);

        Letter::factory()->create([
            'letter_type_id' => $type->id,
            'letter_number' => $this->service->generateNextNumber($type),
            'status' => 'published',
            'created_at' => now(),
        ]);

        $number2 = $this->service->generateNextNumber($type);

        $this->assertStringStartsWith('002/', $number2);
    }

    public function test_it_checks_module_is_inactive_by_default()
    {
        $this->assertFalse($this->service->isActive());
    }

    public function test_it_checks_module_is_active_when_enabled()
    {
        Setting::set('module_persuratan_active', true);

        $this->assertTrue($this->service->isActive());
    }

    public function test_it_uses_custom_numbering_format_from_letter_type()
    {
        $type = LetterType::factory()->create([
            'code' => 'SP',
            'numbering_format' => '{NOMOR}/{CODE}/LPPM/ITSNU.Pkl/{TAHUN}',
        ]);

        $number = $this->service->generateNextNumber($type);

        $this->assertStringStartsWith('001/SP/LPPM/ITSNU.Pkl/'.date('Y'), $number);
        $this->assertStringNotContainsString('BULAN', $number);
    }
}
