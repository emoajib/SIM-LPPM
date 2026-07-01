<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Enums\ReviewStatus;
use App\Livewire\Actions\CompleteReviewAction;
use App\Livewire\Research\Proposal\ReviewerForm;
use App\Models\Proposal;
use App\Models\ProposalReviewer;
use App\Models\Research;
use App\Models\ReviewCriteria;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\InstitutionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $reviewer;

    protected User $randomDosen;

    protected Proposal $proposal;

    protected ProposalReviewer $reviewAssignment;

    protected ReviewCriteria $criteria1;

    protected ReviewCriteria $criteria2;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base roles and data
        $this->seed(RoleSeeder::class);
        $this->seed(InstitutionSeeder::class);

        // Generate users
        $this->reviewer = User::factory()->create(['name' => 'Reviewer Ahli']);
        $this->reviewer->assignRole('reviewer');

        $this->randomDosen = User::factory()->create(['name' => 'Dosen Biasa']);
        $this->randomDosen->assignRole('dosen');

        // Generate criteria
        $this->criteria1 = ReviewCriteria::create([
            'criteria' => 'Relevansi Topik',
            'weight' => 30, // 30% weight
            'type' => 'research',
            'order' => 1,
            'is_active' => true,
        ]);

        $this->criteria2 = ReviewCriteria::create([
            'criteria' => 'Metodologi',
            'weight' => 70, // 70% weight
            'type' => 'research',
            'order' => 2,
            'is_active' => true,
        ]);

        // Generate proposal
        $research = Research::factory()->create();
        $this->proposal = Proposal::factory()->create([
            'submitter_id' => $this->randomDosen->id,
            'detailable_id' => $research->id,
            'detailable_type' => Research::class,
            'status' => ProposalStatus::UNDER_REVIEW,
        ]);

        // Assing reviewer
        $this->reviewAssignment = ProposalReviewer::create([
            'proposal_id' => $this->proposal->id,
            'user_id' => $this->reviewer->id,
            'status' => 'pending',
            'round' => 1,
            'deadline_at' => now()->addDays(7),
        ]);
    }

    public function test_only_assigned_reviewer_can_submit_review()
    {
        // Random dosen tries to review
        $this->actingAs($this->randomDosen);

        $component = Livewire::test(ReviewerForm::class, ['proposalId' => $this->proposal->id]);

        // Ensure canReview is false for random dosen
        $this->assertFalse($component->get('canReview'));

        // Attempt to submit
        $component->call('submitReview', app(CompleteReviewAction::class))
            ->assertHasErrors(['reviewNotes', 'recommendation']); // Fails validation first

        // Provide valid data but still should fail authorization inside submitReview
        $component->set('reviewNotes', 'Ini notes review minimal 10 karakter')
            ->set('recommendation', 'approved')
            ->set("scores.{$this->criteria1->id}.score", 5)
            ->set("scores.{$this->criteria1->id}.acuan", 'Acuan 1')
            ->set("scores.{$this->criteria2->id}.score", 4)
            ->set("scores.{$this->criteria2->id}.acuan", 'Acuan 2')
            ->call('submitReview', app(CompleteReviewAction::class))
            ->assertDispatched('error');
    }

    public function test_review_score_calculation_math_is_accurate()
    {
        $this->actingAs($this->reviewer);

        $component = Livewire::test(ReviewerForm::class, ['proposalId' => $this->proposal->id]);

        $this->assertTrue($component->get('canReview'));

        // Input scores:
        // Criteria 1: Score 4 (Weight 30) -> Value = 120
        // Criteria 2: Score 5 (Weight 70) -> Value = 350
        // Total Expected Score = 470
        $component->set('reviewNotes', 'Catatan review ini cukup panjang untuk lolos validasi.')
            ->set('recommendation', 'approved')
            ->set("scores.{$this->criteria1->id}.score", 4)
            ->set("scores.{$this->criteria1->id}.acuan", 'Kesesuaian topik sangat baik')
            ->set("scores.{$this->criteria2->id}.score", 5)
            ->set("scores.{$this->criteria2->id}.acuan", 'Metodologi sempurna');

        // Confirm computed total score in UI matches expectation mathematically
        $this->assertEquals(470, $component->get('totalScore'));

        // Submit the review
        $component->call('submitReview', app(CompleteReviewAction::class))
            ->assertHasNoErrors()
            ->assertDispatched('review-submitted');

        // Lock verification: database state MUST match the mathematical calculation
        $this->assertDatabaseHas('review_scores', [
            'proposal_reviewer_id' => $this->reviewAssignment->id,
            'review_criteria_id' => $this->criteria1->id,
            'score' => 4,
            'weight_snapshot' => 30,
            'value' => 120, // Mathematically locked
        ]);

        $this->assertDatabaseHas('review_scores', [
            'proposal_reviewer_id' => $this->reviewAssignment->id,
            'review_criteria_id' => $this->criteria2->id,
            'score' => 5,
            'weight_snapshot' => 70,
            'value' => 350, // Mathematically locked
        ]);

        // Verify log creation with total score
        $this->assertDatabaseHas('review_logs', [
            'proposal_reviewer_id' => $this->reviewAssignment->id,
            'total_score' => 470,
            'recommendation' => 'approved',
        ]);

        $this->assertEquals(ReviewStatus::COMPLETED, $this->reviewAssignment->fresh()->status);
    }

    public function test_review_score_cannot_exceed_maximum_boundaries()
    {
        $this->actingAs($this->reviewer);

        $component = Livewire::test(ReviewerForm::class, ['proposalId' => $this->proposal->id]);

        // Attempting to inject a score of 6 (Max is 5)
        $component->set('reviewNotes', 'Catatan review')
            ->set('recommendation', 'approved')
            ->set("scores.{$this->criteria1->id}.score", 6) // Invalid over max
            ->set("scores.{$this->criteria1->id}.acuan", 'Acuan')
            ->set("scores.{$this->criteria2->id}.score", 0) // Invalid under min
            ->set("scores.{$this->criteria2->id}.acuan", 'Acuan')
            ->call('submitReview', app(CompleteReviewAction::class))
            ->assertHasErrors(["scores.{$this->criteria1->id}.score", "scores.{$this->criteria2->id}.score"]);

        // Verify database is completely untouched
        $this->assertDatabaseMissing('review_scores', [
            'proposal_reviewer_id' => $this->reviewAssignment->id,
        ]);
    }

    /**
     * Test dynamic reviewer count requirements for status transition.
     * Vetted by AI - Manual Review Required by Senior Engineer/Manager
     */
    public function test_dynamic_reviewer_count_requirements()
    {
        // 1. When reviewer_count_required = 1
        Setting::set('reviewer_count_required', 1, 'integer');

        $this->actingAs($this->reviewer);
        $component = Livewire::test(ReviewerForm::class, ['proposalId' => $this->proposal->id]);

        // Submit the review with scores for both criteria
        $component->set('reviewNotes', 'Catatan review yang valid dan lengkap.')
            ->set('recommendation', 'approved')
            ->set("scores.{$this->criteria1->id}.score", 5)
            ->set("scores.{$this->criteria1->id}.acuan", 'Acuan 1')
            ->set("scores.{$this->criteria2->id}.score", 4)
            ->set("scores.{$this->criteria2->id}.acuan", 'Acuan 2')
            ->call('submitReview', app(CompleteReviewAction::class));

        // Proposal status should transition to REVIEWED immediately since requirement is 1
        $this->assertEquals(ProposalStatus::REVISION_NEEDED, $this->proposal->fresh()->status);

        // Reset the proposal for the next scenario
        DB::table('proposals')
            ->where('id', $this->proposal->id)
            ->update(['status' => ProposalStatus::UNDER_REVIEW->value]);

        DB::table('proposal_reviewer')
            ->where('id', $this->reviewAssignment->id)
            ->update([
                'status' => ReviewStatus::PENDING->value,
                'completed_at' => null,
                'review_notes' => null,
                'recommendation' => null,
            ]);

        DB::table('review_logs')->where('proposal_id', $this->proposal->id)->delete();
        DB::table('review_scores')->where('proposal_reviewer_id', $this->reviewAssignment->id)->delete();

        // 2. When reviewer_count_required = 2
        Setting::set('reviewer_count_required', 2, 'integer');

        // Submit the first reviewer's review again
        $component = Livewire::test(ReviewerForm::class, ['proposalId' => $this->proposal->id]);
        $component->set('reviewNotes', 'Catatan review yang valid dan lengkap.')
            ->set('recommendation', 'approved')
            ->set("scores.{$this->criteria1->id}.score", 5)
            ->set("scores.{$this->criteria1->id}.acuan", 'Acuan 1')
            ->set("scores.{$this->criteria2->id}.score", 4)
            ->set("scores.{$this->criteria2->id}.acuan", 'Acuan 2')
            ->call('submitReview', app(CompleteReviewAction::class));

        // Proposal status should remain UNDER_REVIEW because requiredCount = 2 and only 1 is assigned/completed
        $this->assertEquals(ProposalStatus::UNDER_REVIEW, $this->proposal->fresh()->status);

        // Assign a second reviewer
        $reviewer2 = User::factory()->create();
        $reviewer2->assignRole('reviewer');
        $reviewAssignment2 = ProposalReviewer::create([
            'proposal_id' => $this->proposal->id,
            'user_id' => $reviewer2->id,
            'status' => 'pending',
            'round' => 1,
            'deadline_at' => now()->addDays(7),
        ]);

        // Submit the second reviewer's review
        $this->actingAs($reviewer2);
        $component2 = Livewire::test(ReviewerForm::class, ['proposalId' => $this->proposal->id]);
        $component2->set('reviewNotes', 'Catatan review kedua yang valid.')
            ->set('recommendation', 'approved')
            ->set("scores.{$this->criteria1->id}.score", 4)
            ->set("scores.{$this->criteria1->id}.acuan", 'Acuan 1')
            ->set("scores.{$this->criteria2->id}.score", 5)
            ->set("scores.{$this->criteria2->id}.acuan", 'Acuan 2')
            ->call('submitReview', app(CompleteReviewAction::class));

        // Now that 2 reviewers have been assigned and completed their review, status should transition to REVIEWED
        $this->assertEquals(ProposalStatus::REVISION_NEEDED, $this->proposal->fresh()->status);
    }
}
