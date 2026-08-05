<?php

namespace Tests\Unit\Services;

use App\Models\Proposal;
use App\Services\DataConsistencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataConsistencyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_safe_fields()
    {
        $proposal = Proposal::factory()->create([
            'title' => 'Old Title',
        ]);

        $service = new DataConsistencyService;

        $result = $service->updateSafeFields($proposal, [
            'title' => 'New Title',
            'summary' => 'Updated summary',
        ]);

        $this->assertTrue($result);
        $proposal->refresh();
        $this->assertEquals('New Title', $proposal->title);
        $this->assertEquals('Updated summary', $proposal->summary);
    }

    public function test_cannot_update_risky_fields()
    {
        $proposal = Proposal::factory()->create([
            'title' => 'Original Title',
        ]);

        $service = new DataConsistencyService;

        $service->updateSafeFields($proposal, [
            'title' => 'Should Work',
            'status' => 'should_be_ignored',
            'budget' => 'should_be_ignored',
        ]);

        $proposal->refresh();
        $this->assertEquals('Should Work', $proposal->title);
        $this->assertNotEquals('should_be_ignored', $proposal->status);
    }
}
