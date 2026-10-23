<?php

namespace Tests\Feature;

use App\Models\BudgetCap;
use App\Models\BudgetGroup;
use App\Services\BudgetValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BudgetValidationTest extends TestCase
{
    use RefreshDatabase;

    protected BudgetValidationService $budgetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->budgetService = app(BudgetValidationService::class);
    }

    public function test_budget_cannot_exceed_yearly_cap()
    {
        $year = (int) date('Y');

        // Arrange: Set Budget Cap for Research to 10.000.000
        BudgetCap::create([
            'year' => $year,
            'research_budget_cap' => 10000000,
            'community_service_budget_cap' => 5000000,
        ]);

        $budgetGroup = BudgetGroup::factory()->create(['name' => 'Bahan', 'percentage' => null]);

        // Act & Assert: Total 11.000.000 > 10.000.000
        $budgetItems = [
            ['total' => 6000000, 'budget_group_id' => $budgetGroup->id],
            ['total' => 5000000, 'budget_group_id' => $budgetGroup->id],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Total anggaran melebihi batas maksimal untuk Penelitian tahun akademik '.$year.'/'.($year + 1).' (Ganjil)');

        $this->budgetService->validateBudgetCap($budgetItems, 'research', $year);
    }

    public function test_budget_within_cap_passes_validation()
    {
        $year = (int) date('Y');

        // Arrange: Set Budget Cap for Research to 10.000.000
        BudgetCap::create([
            'year' => $year,
            'research_budget_cap' => 10000000,
            'community_service_budget_cap' => 5000000,
        ]);

        $budgetGroup = BudgetGroup::factory()->create(['name' => 'Bahan', 'percentage' => null]);

        // Act: Total 9.500.000 < 10.000.000
        $budgetItems = [
            ['total' => 5000000, 'budget_group_id' => $budgetGroup->id],
            ['total' => 4500000, 'budget_group_id' => $budgetGroup->id],
        ];

        // Assert: No exception thrown
        $this->budgetService->validateBudgetCap($budgetItems, 'research', $year);
        $this->assertTrue(true);
    }

    public function test_budget_group_cannot_exceed_allowed_percentage()
    {
        $year = (int) date('Y');

        // Arrange: Set Budget Cap for Research to 100.000.000
        BudgetCap::create([
            'year' => $year,
            'research_budget_cap' => 100000000, // 100 Juta
            'community_service_budget_cap' => 50000000,
        ]);

        // Group 1: Honorarium Max 30%
        $honorGroup = BudgetGroup::factory()->create(['name' => 'Honorarium', 'percentage' => 30]);
        // Group 2: Bahan Habis Pakai Max 60%
        $bahanGroup = BudgetGroup::factory()->create(['name' => 'Bahan Habis Pakai', 'percentage' => 60]);

        // Act: Use 35 Juta for Honorarium (35% of 100 Juta -> Exceeds 30%)
        // Use 40 Juta for Bahan (40% of 100 juta -> Valid)
        $budgetItems = [
            ['total' => 35000000, 'budget_group_id' => $honorGroup->id],
            ['total' => 40000000, 'budget_group_id' => $bahanGroup->id],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Honorarium" melebihi batas MAKSIMAL 30.00%');

        $this->budgetService->validateBudgetGroupPercentages($budgetItems, 'research', $year);
    }

    public function test_system_blocks_budget_submission_if_cap_not_set()
    {
        $year = (int) date('Y');

        // Arrange: No BudgetCap created for this year (or it exists but values are null/0)

        $budgetGroup = BudgetGroup::factory()->create(['name' => 'Honorarium', 'percentage' => 30]);

        // Act: Attempt to submit 1 Juta
        $budgetItems = [
            ['total' => 1000000, 'budget_group_id' => $budgetGroup->id],
        ];

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Batas anggaran untuk Penelitian tahun akademik {$year}/".($year + 1).' (Ganjil) belum diatur');

        $this->budgetService->validateBudgetGroupPercentages($budgetItems, 'research', $year);
    }
}
