<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Actions\AssignReviewersAction;
use App\Livewire\Actions\CompleteReviewAction;
use App\Models\Proposal;
use App\Models\Research;
use App\Models\ReviewCriteria;
use App\Models\ReviewScore;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PessimisticSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $dosen;

    protected User $adminLppm;

    protected User $reviewer;

    protected Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        $this->dosen = User::factory()->create();
        $this->dosen->assignRole('dosen');

        $this->adminLppm = User::factory()->create();
        $this->adminLppm->assignRole('admin lppm');

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('reviewer');

        $research = Research::factory()->create();
        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->dosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::WAITING_REVIEWER,
        ]);
    }

    /**
     * @test Case 1.1: Block Submitter as Reviewer (CoI)
     */
    public function test_cannot_assign_submitter_as_reviewer()
    {
        $this->actingAs($this->adminLppm);
        $action = app(AssignReviewersAction::class);

        $result = $action->execute($this->proposal, $this->dosen->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Pengusul tidak boleh menjadi reviewer', $result['message']);
    }

    /**
     * @test Case 1.2: Block Team Member as Reviewer (CoI)
     */
    public function test_cannot_assign_team_member_as_reviewer()
    {
        $teamMember = User::factory()->create();
        $this->proposal->teamMembers()->attach($teamMember->id, ['role' => 'anggota', 'status' => 'accepted']);

        $this->actingAs($this->adminLppm);
        $action = app(AssignReviewersAction::class);

        $result = $action->execute($this->proposal, $teamMember->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Anggota tim proposal tidak boleh menjadi reviewer', $result['message']);
    }

    /**
     * @test Case 2.1: Block Complete Review without Scores (Anti-Ghost)
     */
    public function test_cannot_complete_review_without_scores()
    {
        // Setup review assignment
        $this->actingAs($this->adminLppm);
        app(AssignReviewersAction::class)->execute($this->proposal, $this->reviewer->id);

        $review = $this->proposal->reviewers()->first();

        // Attempt complete without creating ReviewScore
        $this->actingAs($this->reviewer);
        $action = app(CompleteReviewAction::class);

        $result = $action->execute($review, 'Nice proposal', 'approved');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('wajib mengisi skor penilaian', $result['message']);
        $this->assertNotEquals(ReviewStatus::COMPLETED->value, $review->fresh()->status);
    }

    /**
     * @test Case 3.1: Zero Trust - Block Hijacking Review
     */
    public function test_user_cannot_complete_someone_elses_review()
    {
        // Setup review for Reviewer A
        $this->actingAs($this->adminLppm);
        app(AssignReviewersAction::class)->execute($this->proposal, $this->reviewer->id);
        $review = $this->proposal->reviewers()->first();

        // Attacker (Other Reviewer)
        $attacker = User::factory()->create();
        $attacker->assignRole('reviewer');

        $this->actingAs($attacker);
        $action = app(CompleteReviewAction::class);

        $result = $action->execute($review, 'Malicious hack', 'rejected');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Anda bukan reviewer yang ditugaskan', $result['message']);
    }

    /**
     * @test Case 3.2: Atomic Transaction Integrity
     */
    public function test_proposal_status_only_changes_when_all_reviewers_submit_successfully()
    {
        Setting::set('reviewer_count_required', 2, 'integer');

        // Assign 2 reviewers
        $otherReviewer = User::factory()->create();
        $otherReviewer->assignRole('reviewer');

        $this->actingAs($this->adminLppm);
        app(AssignReviewersAction::class)->execute($this->proposal, $this->reviewer->id);
        app(AssignReviewersAction::class)->execute($this->proposal, $otherReviewer->id);

        $criteria = ReviewCriteria::create([
            'criteria' => 'Relevansi',
            'type' => 'research',
            'weight' => 20,
            'is_active' => true,
            'order' => 1,
        ]);

        // Reviewer 1 Submits (With Scores)
        $this->actingAs($this->reviewer);
        $review1 = $this->proposal->reviewers()->where('user_id', $this->reviewer->id)->first();
        ReviewScore::create([
            'proposal_reviewer_id' => $review1->id,
            'review_criteria_id' => $criteria->id,
            'score' => 5,
            'round' => 1,
            'acuan' => 'Evidence',
            'weight_snapshot' => $criteria->weight,
            'value' => 5 * $criteria->weight,
        ]);

        app(CompleteReviewAction::class)->execute($review1, 'Good', 'approved');

        // Proposal should still be UNDER_REVIEW (waiting for reviewer 2)
        $this->assertEquals(ProposalStatus::UNDER_REVIEW, $this->proposal->fresh()->status);

        // Reviewer 2 Submits (With Scores)
        $this->actingAs($otherReviewer);
        $review2 = $this->proposal->reviewers()->where('user_id', $otherReviewer->id)->first();
        ReviewScore::create([
            'proposal_reviewer_id' => $review2->id,
            'review_criteria_id' => $criteria->id,
            'score' => 4,
            'round' => 1,
            'acuan' => 'Evidence',
            'weight_snapshot' => $criteria->weight,
            'value' => 4 * $criteria->weight,
        ]);

        app(CompleteReviewAction::class)->execute($review2, 'Okay', 'approved');

        // Now it should be REVIEWED
        $this->assertEquals(ProposalStatus::REVIEWED, $this->proposal->fresh()->status);
    }
}
